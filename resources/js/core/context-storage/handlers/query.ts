import {
  ContextStorageHandler,
  ContextStorageHandlerConstructor,
  RegisterBaseOptions,
} from '@/core/context-storage/handlers'
import { deserializeParams, serializeParams } from '@/core/context-storage/handlers/query-helpers'
import { contextStorageQueryHandler } from '@/core/context-storage/symbols'
import { cloneDeep, isEqual, merge, pick } from 'lodash'
import { getCurrentInstance, Ref, toValue, UnwrapNestedRefs, watch, WatchHandle } from 'vue'
import { LocationQuery, LocationQueryValue } from 'vue-router'

interface QueryHandlerSharedOptions {
  /**
   * Default - false
   *
   * If enabled - empty state will be preserved in query.
   *
   * Useful, when you have default values, and want to preserve empty state in query.
   * @example
   * ```
   * Options: {preserveEmptyState: true, prefix: 'filters'}
   *
   * When filters are empty we will get this in query string:
   *
   * /list?filters
   *
   * After page reload state will be not restored to default
   * ```
   *
   * @example
   * ```
   * Options: {preserveEmptyState: false, prefix: 'filters'}
   *
   * When filters are empty we will get this in query string:
   *
   * /list
   *
   * After page reload state will be restored to default
   * ```
   *
   * @example
   * ```
   * Options: {preserveEmptyState: true}
   *
   * When filters are empty we will get this in query string:
   *
   * /list?_
   *
   * After page reload state will be not restored to default.
   * Underscore (_) is default value for emptyPlaceholder option
   * ```
   */
  preserveEmptyState?: boolean
  /**
   * Default - true
   *
   * If transform option is not passed, ref will be merged with query only by keys that exists in ref.
   */
  mergeOnlyExistingKeysWithoutTransform?: boolean
}

interface QueryHandlerBaseOptions extends QueryHandlerSharedOptions {
  /**
   * Default: replace
   *
   * Vue-router navigate mode.
   * Use push if you want to add new query to history.
   * Use replace if you want to replace current query without adding to history.
   */
  mode?: 'replace' | 'push'
  /**
   * Default: _
   *
   * Placeholder for empty state, used when preserveEmptyState is true and all ref values are empty.
   */
  emptyPlaceholder?: string
  /**
   * Default: false
   *
   * If enabled - unused keys will be preserved in query.
   * Unused keys are keys, that are not exists in ref.
   */
  preserveUnusedKeys?: boolean
}

interface RegisterQueryHandlerBaseOptions<
  T extends Record<string, unknown> = {},
> extends QueryHandlerSharedOptions {
  /**
   * Prefix in query string.
   *
   * @example
   * ```
   * filters, table-1[filters], table-2[filters]
   * ```
   */
  prefix?: string
  transform?: (
    deserialized: DeepTransformValuesToLocationQueryValue<UnwrapNestedRefs<T>>,
    initialData: T,
  ) => UnwrapNestedRefs<T>
}

interface RegisterQueryHandlerOptions<T extends Record<string, unknown> = {}>
  extends RegisterBaseOptions, RegisterQueryHandlerBaseOptions<T> {}

export interface IContextStorageQueryHandler<
  T extends Record<string, unknown> = {},
> extends ContextStorageHandler {
  register: (data: Ref<T>, options: RegisterQueryHandlerOptions<T>) => () => void
}

export function useContextStorageQueryHandler<T extends Record<string, unknown>>(
  data: Ref<T>,
  options?: RegisterQueryHandlerBaseOptions<T>,
) {
  const handler = inject<InstanceType<typeof ContextStorageQueryHandler>>(
    contextStorageQueryHandler,
  )

  if (!handler) {
    throw new Error('ContextStorageQueryHandler is not provided')
  }

  const currentInstance = getCurrentInstance()
  const uid = currentInstance?.uid || 0

  const causer = new Error().stack?.split('\n')[2]?.trimStart() || 'unknown'

  const stop = handler.register(data, { causer, uid, ...options })
  onBeforeUnmount(() => {
    stop()
  })
}

interface RegisteredWatcher<T extends Record<string, unknown>> {
  data: Ref<T>
  initialData: T
  options: RegisterQueryHandlerOptions<T>
  watchHandle: WatchHandle
}

function sortQueryByReference(query: LocationQuery, ...references: LocationQuery[]): LocationQuery {
  const sorted: LocationQuery = {}

  const referenceKeys = new Set<string>()

  // Добавляем ключи в порядке references
  references.forEach((reference) => {
    Object.keys(reference).forEach((key) => {
      referenceKeys.add(key)
    })
  })

  referenceKeys.forEach((key) => {
    if (key in query && !(key in sorted)) {
      sorted[key] = query[key]
    }
  })

  // Добавляем оставшиеся ключи из query, которых нет в sorted
  Object.keys(query).forEach((key) => {
    if (!(key in sorted)) {
      sorted[key] = query[key]
    }
  })

  return sorted
}

export type QueryValue = LocationQueryValue | LocationQueryValue[]

// A recursive type that transforms all properties to CustomType
type DeepTransformValuesToLocationQueryValue<T> = {
  [K in keyof T]?: T[K] extends object // Check if the property is an object
    ? // Exclude Array from being treated as an object for recursion
      T[K] extends Array<any>
      ? QueryValue // Arrays will just become the CustomType (or you could handle them differently)
      : DeepTransformValuesToLocationQueryValue<T[K]> // Recursively apply the type to nested objects
    : QueryValue // Non-object (leaf) properties get the CustomType
}

export const ContextStorageQueryHandler = class ContextStorageQueryHandler implements IContextStorageQueryHandler {
  private enabled = false
  private registered: RegisteredWatcher<any>[] = []
  private currentQuery: LocationQuery | undefined = undefined
  private readonly route: ReturnType<typeof useRoute>
  private router: ReturnType<typeof useRouter>
  private initialState?: Record<string, unknown>
  private hasAnyRegistered = false
  private preventSyncRegisteredToQueryByAfterEachRoute = false
  private preventAfterEachRouteCallsWhileCallingRouter = false

  static customQueryHandlerOptions = {} satisfies QueryHandlerBaseOptions

  private readonly options: Required<QueryHandlerBaseOptions> = {
    mode: 'replace',
    emptyPlaceholder: '_',
    mergeOnlyExistingKeysWithoutTransform: true,
    preserveUnusedKeys: false,
    preserveEmptyState: false,
  }

  static configure(options: QueryHandlerBaseOptions): ContextStorageHandlerConstructor {
    ContextStorageQueryHandler.customQueryHandlerOptions = options

    return ContextStorageQueryHandler
  }

  constructor() {
    this.route = useRoute()
    this.router = useRouter()

    this.options = {
      ...this.options,
      ...ContextStorageQueryHandler.customQueryHandlerOptions,
    }

    const stopAfterEach = this.router.afterEach(() => {
      this.afterEachRoute()
    })

    onBeforeUnmount(() => {
      stopAfterEach()
    })
  }

  getInjectionKey() {
    return contextStorageQueryHandler
  }

  setInitialState(state: Record<string, unknown> | undefined) {
    this.initialState = state
  }

  static getInitialStateResolver() {
    const route = useRoute()

    return () => route.query
  }

  setEnabled(state: boolean, initial: boolean) {
    const prevState = this.enabled
    this.enabled = state

    if (this.hasAnyRegistered) {
      if (initial) {
        this.syncInitialStateToRegistered()
      }

      if ((state && !prevState) || !initial) {
        this.syncRegisteredToQuery()
      }
    }
  }

  async syncRegisteredToQuery() {
    if (!this.enabled) {
      return
    }

    if (this.preventSyncRegisteredToQueryByAfterEachRoute) {
      return
    }

    const { newQuery, newQueryRaw } = this.#buildQueryFromRegistered()

    this.currentQuery = newQueryRaw

    if (isEqual(newQuery, this.route.query)) {
      return
    }

    this.preventAfterEachRouteCallsWhileCallingRouter = true
    try {
      if (this.options.mode === 'replace') {
        await this.router.replace({ ...this.route, query: newQuery })
      } else {
        await this.router.push({ ...this.route, query: newQuery })
      }
    } catch (e) {
      console.error('Got error while routing', e)
    }
    this.preventAfterEachRouteCallsWhileCallingRouter = false
  }

  afterEachRoute() {
    if (!this.enabled) {
      return
    }

    if (this.preventAfterEachRouteCallsWhileCallingRouter) {
      return
    }

    this.setInitialState(this.route.query)

    this.preventSyncRegisteredToQueryByAfterEachRoute = true
    queueMicrotask(() => {
      this.preventSyncRegisteredToQueryByAfterEachRoute = false

      this.syncInitialStateToRegistered()
      this.syncRegisteredToQuery()
    })

    setTimeout(() => {
      this.syncInitialStateToRegistered()
      this.syncRegisteredToQuery()
    })
  }

  /**
   * Берёт данные из initialState и заполняет ими данные registered item
   */
  syncInitialStateToRegisteredItem<T extends Record<string, unknown>>(item: RegisteredWatcher<T>) {
    if (this.initialState === undefined) {
      return
    }

    let deserialized = deserializeParams(this.initialState)

    const prefix = item.options?.prefix

    if (typeof prefix === 'string' && prefix.length > 0) {
      deserialized = deserialized[prefix]
    }

    if (deserialized === undefined) {
      return
    }

    /**
     * null может быть, если get() вернул null, например с prefix: "filters", запрос /?filters превращается в {filters: null}
     */
    if (deserialized !== null) {
      const deserializedKeys = Object.keys(deserialized)

      /**
       * Если данные пусты - возвращаем начальное значение.
       *
       * Может происходить при прямом переходе на route, например через пункт в меню.
       */
      if (!deserializedKeys.length) {
        merge(item.data.value, item.initialData)
        return
      }

      if (deserializedKeys.length === 1 && deserialized[this.options.emptyPlaceholder] === null) {
        delete deserialized[this.options.emptyPlaceholder]
      }
    }

    // Трансформация полезна, когда числа (created_at_from) превращаются в строки при deserializeParams.
    if (item.options?.transform) {
      deserialized = item.options.transform(deserialized, item.initialData)
    } else {
      const mergeOnlyExistingKeysWithoutTransform = true
      if (mergeOnlyExistingKeysWithoutTransform) {
        //Заполняем только те ключи, которые присутствуют в initialData
        deserialized = pick(deserialized, Object.keys(item.initialData))
      }
    }

    if (isEqual(item.data.value, deserialized)) {
      return
    }

    merge(item.data.value, deserialized)
  }

  syncInitialStateToRegistered() {
    this.registered.forEach((item) => this.syncInitialStateToRegisteredItem(item))
  }

  register<T extends Record<string, unknown>>(
    data: Ref<T>,
    options: RegisterQueryHandlerOptions<T>,
  ) {
    this.hasAnyRegistered = true

    const watchHandle = watch(data, () => this.syncRegisteredToQuery(), {
      deep: true,
      // immediate: true,
    })

    const item: RegisteredWatcher<T> = {
      data,
      initialData: cloneDeep(data.value),
      options,
      watchHandle,
    }
    this.registered.push(item)

    const syncCallback = () => {
      this.syncInitialStateToRegisteredItem(item)
      this.syncRegisteredToQuery()
    }

    if (this.preventAfterEachRouteCallsWhileCallingRouter) {
      /**
       * Macrotask solves syncing issues when syncRegisteredToQuery called after HMR
       */
      setTimeout(syncCallback)
    } else {
      queueMicrotask(syncCallback)
    }

    return () => {
      this.registered.splice(this.registered.indexOf(item), 1)
      this.syncRegisteredToQuery()
    }
  }

  #buildQueryFromRegistered() {
    const newQueryRaw: LocationQuery = {}

    this.registered.forEach((item) => {
      const { prefix, preserveEmptyState = this.options.preserveEmptyState } = item.options || {}
      const patch = serializeParams(toValue(item.data), {
        prefix,
      })

      const patchKeys = Object.keys(patch)

      //Если есть пересечения ключей между query и патчем, то выводим предупреждение.
      //Патчи не должны затирать друг-друга, иначе при перезагрузке восстановится некорректное значение.
      patchKeys.forEach((key) => {
        if (newQueryRaw.hasOwnProperty(key)) {
          console.warn(`Key ${key} is already present, overriding ` + (item.options?.causer || ''))
        }
      })

      if (!patchKeys.length && preserveEmptyState) {
        patch[prefix || this.options.emptyPlaceholder] = null
      }

      Object.assign(newQueryRaw, patch)
    })

    let newQuery = { ...newQueryRaw }

    /**
     * Не будет удалять из query ключи, которые не используются в patch.
     *
     * Будет работать только если у registered item есть transform, иначе без
     * него - все ключи попадают в item.data при первичном заполнении из initialState
     */
    if (this.options.preserveUnusedKeys) {
      newQuery = { ...this.route.query, ...newQuery }
    }

    if (this.currentQuery !== undefined) {
      //Делаем diff ключей currentQuery и newQueryCleaned, и те ключи, которые есть в currentQuery, но нет в newQueryCleaned, ставим в newQueryCleaned как undefined.
      //Нужно для того, чтобы в query строке не оставались ключи, которые больше не используются
      Object.keys(this.currentQuery).forEach((key) => {
        if (!newQueryRaw.hasOwnProperty(key)) {
          delete newQuery[key]
        }
      })
    }

    if (Object.keys(newQuery).length > 1 && newQuery[this.options.emptyPlaceholder] === null) {
      delete newQuery[this.options.emptyPlaceholder]
    }

    newQuery = sortQueryByReference(newQuery, newQueryRaw)

    return { newQuery, newQueryRaw }
  }
}
