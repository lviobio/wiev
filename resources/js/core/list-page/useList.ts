import { MaybePaginatedData, PaginationComposable, usePagination } from '@/core/pagination/base'
import { SortingComposable, useSorting } from '@/core/sorting/base'
import { debounce, isEqual } from 'lodash'
import { onScopeDispose, Reactive, ref, Ref, watch } from 'vue'

type WatchHandle = ReturnType<typeof watch>

export interface UseListOptions<T, F extends Record<string, unknown>> {
  filters: F
  debounceMs?: number
  loader: (options: UseListParams<F> & { signal: AbortSignal }) => Promise<MaybePaginatedData<T>>
}

export interface UseListParams<F extends Record<string, unknown>> {
  pagination: PaginationComposable
  sorting: SortingComposable
  search: Ref<string | undefined>
  filters: Reactive<F>
}

export interface UseListResult<T, F extends Record<string, unknown>> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  params: UseListParams<F>
  load: () => Promise<void>
  enableWatchers: () => void
}

export function useList<T, F extends Record<string, unknown>>(
  options: UseListOptions<T, F>,
): UseListResult<T, F> {
  const items = shallowRef<T[]>([])
  const loading = ref(false)
  const pagination = usePagination()
  const sorting = useSorting()
  const search = ref<string | undefined>('')
  const filters = reactive(options.filters)

  const { loader, debounceMs = 400 } = options

  const params: UseListResult<T, F>['params'] = {
    pagination,
    sorting,
    search,
    filters,
  }

  let abortController: AbortController | undefined
  let paginationWatch: WatchHandle | undefined

  const load = async (): Promise<void> => {
    abortController?.abort()
    abortController = new AbortController()
    loading.value = true
    paginationWatch?.pause()

    try {
      const result = await loader({ ...params, signal: abortController.signal })
      items.value = result.data
      pagination.applyMeta(result.meta)
    } finally {
      loading.value = false
      paginationWatch?.resume()
    }
  }

  const loadDebounced = debounce(load, debounceMs)

  const enableWatchers = () => {
    watch(
      () => structuredClone(toRaw(params.filters)),
      (newVal, oldVal) => {
        if (isEqual(newVal, oldVal)) return
        params.pagination?.resetPage()
        loadDebounced()
      },
      { deep: true },
    )

    watch(params.search, (newVal, oldVal) => {
      if (newVal === oldVal) return
      params.pagination?.resetPage()
      loadDebounced()
    })

    watch(
      () => params.sorting.state.value,
      (newVal, oldVal) => {
        if (isEqual(newVal, oldVal)) return
        params.pagination?.resetPage()
        load()
      },
    )

    paginationWatch = watch(
      () => params.pagination?.params.value,
      (newVal, oldVal) => {
        if (isEqual(newVal, oldVal)) return
        load()
      },
      { deep: true },
    )
  }

  onScopeDispose(() => {
    loadDebounced.cancel()
    abortController?.abort()
  })

  return {
    items,
    loading,
    params,
    load,
    enableWatchers,
  }
}
