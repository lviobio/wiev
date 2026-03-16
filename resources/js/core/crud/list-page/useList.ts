import { isCancel } from '@/core/errors'
import { MaybePaginatedData, PaginationComposable, usePagination } from '@/core/pagination/base'
import { SortingComposable, useSorting } from '@/core/sorting/base'
import { watchIgnorable, WatchIgnorableReturn } from '@vueuse/core'
import { debounce, isEqual } from 'lodash'
import { onScopeDispose, Reactive, ref, Ref, watch } from 'vue'

export interface UseListOptions<T, F extends Record<string, unknown>> {
  filters: Reactive<F>
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
    filters: options.filters,
  }

  let abortController: AbortController | undefined
  let paginationWatch: WatchIgnorableReturn | undefined

  const load = async (): Promise<void> => {
    loading.value = true
    abortController?.abort()
    const currentController = new AbortController()
    abortController = currentController

    try {
      const result = await loader({ ...params, signal: currentController.signal })
      items.value = result.data
      paginationWatch?.ignoreUpdates(() => {
        params.pagination.applyMeta(result.meta)
      })
    } catch (e: unknown) {
      if (isCancel(e)) {
        return
      }

      throw e
    } finally {
      if (abortController === currentController) {
        loading.value = false
      }
    }
  }

  const loadDebounced = debounce(load, debounceMs)
  const loadImmediate = () => {
    loadDebounced.cancel()
    load()
  }

  const enableWatchers = () => {
    watch(
      params.filters,
      () => {
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

        loadImmediate()
      },
    )

    paginationWatch = watchIgnorable(
      () => params.pagination?.params.value,
      (newVal, oldVal) => {
        // Skip if nothing changed
        if (isEqual(newVal, oldVal)) return

        // Reset page if per_page changed
        if (newVal?.per_page !== oldVal?.per_page) {
          paginationWatch?.ignoreUpdates(() => {
            params.pagination?.resetPage()
          })
        }

        loadImmediate()
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
