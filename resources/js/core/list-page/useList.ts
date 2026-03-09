import { MaybePaginatedData, PaginationComposable, usePagination } from '@/core/pagination/base'
import { SortingComposable, useSorting } from '@/core/sorting/base'
import { watchIgnorable } from '@vueuse/core'
import { WatchIgnorableReturn } from '@vueuse/shared'
import { debounce, isEqual } from 'lodash'
import { onScopeDispose, Reactive, ref, Ref, watch } from 'vue'

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

  const { loader, debounceMs = 400 } = options

  const params: UseListResult<T, F>['params'] = {
    pagination: usePagination(),
    sorting: useSorting(),
    search: ref<string | undefined>(''),
    filters: reactive(options.filters),
  }

  let abortController: AbortController | undefined
  let paginationWatch: WatchIgnorableReturn | undefined

  const load = async (): Promise<void> => {
    abortController?.abort()
    abortController = new AbortController()
    loading.value = true

    try {
      const result = await loader({ ...params, signal: abortController.signal })
      items.value = result.data
      paginationWatch?.ignoreUpdates(() => {
        params.pagination.applyMeta(result.meta)
      })
    } finally {
      loading.value = false
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
        loadDebounced.cancel()
        load()
      },
    )

    paginationWatch = watchIgnorable(
      () => params.pagination?.params.value,
      (newVal, oldVal) => {
        if (isEqual(newVal, oldVal)) return
        loadDebounced.cancel()
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
