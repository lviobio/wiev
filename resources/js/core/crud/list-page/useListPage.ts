import AppDataTable from '@/components/AppDataTable.vue'
import type { TableFiltering } from '@/components/AppDataTable/filters'
import { Search24Regular } from '@vicons/fluent'
import { NFlex, NIcon, NInput } from 'naive-ui'
import { computed, defineComponent, h, markRaw, toValue, type Component, type Reactive } from 'vue'
import { z } from 'zod'
import {
  actionsColumn,
  createColumnHelpers,
  isActionsColumn,
  processActionsColumn,
} from './columns'
import {
  installFeatures,
  withFilters,
  withPagination,
  withSearch,
  withSorting,
  type FeatureContext,
} from './features'
import {
  ActionsColumnMarker,
  DataLoader,
  DefaultListFeaturesState,
  LIST_PAGE_ACTIONS_SYMBOL,
  ListComposables,
  ListContextSymbol,
  ListPageColumn,
  UseListPageOptions,
  UseListPageReturn,
} from './types'
import { useList } from './useList'
import { useListAdapters } from './useListAdapters'

/**
 * Create a full list page composable with ready-to-use components.
 *
 * Types are inferred automatically:
 * - `T` (row type) from `dataHandler`'s return type
 * - `F` (filters) from `filtersSchema` via `z.infer`
 *
 * `dataHandler` is a separate argument (not an option) on purpose: when a
 * generic call like `makeDataHandlerFromRepositoryAdapter(...)` sits in the
 * same object literal as untyped callbacks (`(row) => ...` in columns/actions),
 * TypeScript fixes `T` to its constraint before resolving the inner call and
 * inference collapses to `Record<string, any>`. As a standalone first argument
 * it is processed first, so `T` flows into all column/action callbacks.
 *
 * For the same reason `columns` accepts a factory callback that receives
 * column helpers with `T` pre-bound — calling generic helpers like
 * `linkColumn(...)` directly inside the options object would lose `T`.
 *
 * @example
 * ```ts
 * const ListPage = useListPage(
 *   makeDataHandlerFromRepositoryAdapter(repository.list.bind(repository)),
 *   {
 *     filtersSchema: postListFiltersSchema,
 *     columns: ({ column, linkColumn, dateColumn }) => [
 *       linkColumn('id', { to: (row) => ({ name: 'posts.show', params: { id: row.id } }) }),
 *       column('title', { sorter: true }),
 *       dateColumn('created_at', { width: 200 }),
 *     ],
 *     actions: [...],
 *     search: { placeholder: 'Search' },
 *   },
 * )
 *
 * // Template:
 * // <ListPage.Component />          — full page
 * // <ListPage.Partial.Table />      — table only
 * // <ListPage.Partial.Search />     — search only
 * // <ListPage.Partial.Wrapper />    — wrapper with slots
 * ```
 */
export function useListPage<
  T extends Record<string, any>,
  FS extends z.ZodObject,
  FeaturesState extends Record<string, unknown> = DefaultListFeaturesState<z.infer<FS>>,
>(
  dataHandler: DataLoader<T, FeaturesState>,
  options: UseListPageOptions<T, FS>,
): UseListPageReturn<T, z.infer<FS>> {
  type FST = z.infer<FS>
  const {
    search: searchConfig,
    table: tableConfig,
    contextSymbol = ListContextSymbol,
  } = options

  const context = contextSymbol ? inject(contextSymbol, undefined) : undefined

  const composables: ListComposables = {
    router: useRouter(),
    message: useMessage(),
    dialog: useDialog(),
  }

  if (!options.filtersSchema && !options.features) {
    throw new Error('useListPage - filtersSchema or features must be provided')
  }

  // ── Core list state ───────────────────────────────────────────

  const features = {} as FeaturesState

  const list = useList({
    debounceMs: options.debounceMs,
    loader: (signal) =>
      dataHandler({
        features,
        signal,
      }),
  })

  const { items, loading, load } = list

  // ── Feature installation ──────────────────────────────────────

  const shared = new Map<symbol, unknown>()

  const ctx: FeatureContext = {
    loadDebounced: list.loadDebounced,
    loadImmediate: list.loadImmediate,
    onAfterLoad: list.onAfterLoad,
    provide: (key, value) => shared.set(key, value),
    resolve: <R = unknown>(key: symbol) => shared.get(key) as R | undefined,
  }

  const featuresList = options.features ?? [
    withPagination(),
    withSorting(),
    withSearch(),
    withFilters(options.filtersSchema!, { items: options.filters }),
  ]

  const { state: installedState, contributions, contextSync } = installFeatures(featuresList, ctx)
  Object.assign(features as object, installedState)

  // ── Adapters ──────────────────────────────────────────────────

  const adapters = useListAdapters({
    features,
    context,
    contextSyncChannels: contextSync,
  })

  // ── Feature-contributed filtering (if withFilters is installed) ─

  const filtering = contributions.table.filtering as TableFiltering | undefined

  // ── Column processing ─────────────────────────────────────────

  /**
   * Process raw columns:
   * 1. Auto-append actionsColumn() if `actions` provided but no marker in columns
   * 2. Inject sortOrder for columns with `sorter: true`
   * 3. Inject render function into actionsColumn marker from `options.actions`
   * 4. Auto-add `filter: true` for columns matching a filter definition
   */
  const columnHelpers = createColumnHelpers<T>(composables)

  const processedColumns = computed(() => {
    const rawColumns = [...(options.columns?.(columnHelpers) ?? [])]

    // Auto-append actions column if actions are provided but no marker exists
    if (options.actions?.length && !rawColumns.some(isActionsColumn)) {
      rawColumns.push(actionsColumn())
    }

    return rawColumns.map((col: ListPageColumn<T>) => {
      const processed = { ...col } as any

      // Inject sortOrder for sortable columns
      if ('sorter' in processed && processed.sorter && 'key' in processed) {
        processed.sortOrder = adapters.getSortOrder(String(processed.key))
      }

      // Inject actions render function into the marker column
      if (isActionsColumn(col) && options.actions?.length) {
        const marker = (col as any)[LIST_PAGE_ACTIONS_SYMBOL] as ActionsColumnMarker
        let finalActions = options.actions

        if (typeof marker === 'function') {
          const result = marker({ actions: options.actions })
          finalActions = result.actions as typeof finalActions
          if (result.title) processed.title = result.title
          if (result.width) processed.width = result.width
        }

        const actionsRender = processActionsColumn<T>(composables, finalActions, load)
        processed.render = actionsRender.render
        delete processed[LIST_PAGE_ACTIONS_SYMBOL]
      }

      // Auto-add filter: true for columns that have a matching filter
      if (filtering && 'key' in processed) {
        const hasFilter = filtering.items.some((f) => f.key === processed.key)
        if (hasFilter && processed.filter === undefined) {
          processed.filter = true
        }
      }

      return processed
    })
  })

  // ── Components ────────────────────────────────────────────────

  const SearchComponent = markRaw(
    defineComponent({
      name: 'ListPageSearch',
      setup() {
        const { placeholder = () => 'Search' } = searchConfig || {}

        return () => {
          if (searchConfig === false) return null

          return h(
            NInput,
            {
              value: adapters.search?.value || '',
              'onUpdate:value': (val: string) => {
                if (adapters.search) adapters.search.value = val !== '' ? val : undefined
              },
              placeholder: toValue(placeholder),
              clearable: true,
            },
            {
              prefix: () => h(NIcon, null, { default: () => h(Search24Regular) }),
            },
          )
        }
      },
    }),
  ) as Component

  const TableComponent = markRaw(
    defineComponent({
      name: 'ListPageTable',
      props: {
        columns: { type: Array, default: undefined },
      },
      setup(props) {
        return () => {
          const cols = (props.columns as any) ?? processedColumns.value

          return h(
            AppDataTable as any,
            {
              columns: cols,
              data: items.value,
              loading: loading.value,
              loader: load,
              size: tableConfig?.size ?? 'small',
              pagination: adapters.dataTablePagination.value,
              extraActiveFilters: adapters.searchActiveFilters.value,
              remote: tableConfig?.remote ?? true,
              striped: tableConfig?.striped ?? true,
              'onUpdate:sorter': adapters.onUpdateSorter,
              ...contributions.table,
            },
            {
              header: () => h('div', [searchConfig !== false ? h(SearchComponent) : null]),
            },
          )
        }
      },
    }),
  ) as Component

  const WrapperComponent = markRaw(
    defineComponent({
      name: 'ListPageWrapper',
      setup(_, { slots }) {
        return () => h(NFlex, null, { default: () => slots.default?.() })
      },
    }),
  ) as Component

  const FullComponent = markRaw(
    defineComponent({
      name: 'ListPageComponent',
      setup() {
        return () =>
          h(WrapperComponent, null, {
            default: () => h(TableComponent),
          })
      },
    }),
  ) as Component

  // ── Return ────────────────────────────────────────────────────

  return {
    Component: FullComponent,

    Partial: {
      Wrapper: WrapperComponent,
      Table: TableComponent,
      Search: SearchComponent,
    },

    state: {
      items,
      loading,
      filters: adapters.filters as Reactive<FST> | undefined,
      search: adapters.search,
      pagination: adapters.pagination,
      sorting: adapters.sorting,
    },

    actions: {
      load,
    },
  }
}
