import { MaybePaginatedData, PaginationComposable, usePagination } from '@/core/pagination/base'
import { SortingComposable, useSorting } from '@/core/sorting/base'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import {
  postListFiltersSchema,
  usePostRepository,
} from '@/modules/post/repositories/PostRepository'
import { Post } from '@/modules/post/types'
import { debounce, isEqual } from 'lodash'
import { onScopeDispose, Reactive, ref, Ref } from 'vue'
import { z } from 'zod'

// interface SyncListData<F> {
//   filters: Ref<F>
//   pagination: PaginationComposable
// }

interface UseListOptions<T, F extends Record<string, unknown>> {
  filters: F
  debounceMs?: number
  loader: (options: UseListParams<F> & { signal: AbortSignal }) => Promise<MaybePaginatedData<T>>
}

interface UseListParams<F extends Record<string, unknown>> {
  pagination: PaginationComposable
  sorting: SortingComposable
  search: Ref<string | undefined>
  filters: Reactive<F>
}

interface UseListResult<T, F extends Record<string, unknown>> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  params: UseListParams<F>
  load: () => void
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
  let isPaginationWatchPaused = false

  const load = async (): Promise<void> => {
    abortController?.abort()
    abortController = new AbortController()
    loading.value = true
    isPaginationWatchPaused = true

    try {
      const result = await loader({ ...params, signal: abortController.signal })
      items.value = result.data
      pagination.applyMeta(result.meta)
    } finally {
      loading.value = false
      isPaginationWatchPaused = false
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

    watch(
      () => params.pagination?.params.value,
      (newVal, oldVal) => {
        if (isPaginationWatchPaused) return
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

/**
 * options?: {
 *   syncCallback?: (data: SyncListData) => void
 *   context?: (data: SyncListData) => void
 * }
 */
export function usePostsList() {
  const repository = usePostRepository()

  const list = useList<Post, z.infer<typeof postListFiltersSchema>>({
    filters: createEmptyObjectFromSchema(postListFiltersSchema),
    loader: async ({ filters, pagination, sorting, signal }) => {
      return repository.list({
        data: {
          filters: toRaw(filters),
        },
        pagination,
        sorting,
        signal,
      })
    },
  })

  return {
    ...list,
    repository,
  }
}
