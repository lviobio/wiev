import { PaginationComposable, usePagination } from '@/core/pagination/base'
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

interface UseListOptions<F extends Record<string, unknown>> {
  filters: F
  loader: (options: UseListParams<F> & { signal: AbortSignal }) => Promise<void>
}

interface UseListParams<F extends Record<string, unknown>> {
  pagination: PaginationComposable
  search: Ref<string | undefined>
  filters: Reactive<F>
}

interface UseListOptionsResult<T, F extends Record<string, unknown>> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  params: UseListParams<F>
  load: () => void
  enableWatchers: () => void
}

export function useList<T, F extends Record<string, unknown>>(
  options: UseListOptions<F>,
): UseListOptionsResult<T, F> {
  const items = shallowRef<T[]>([])
  const loading = ref(false)
  const pagination = usePagination()
  const search = ref<string | undefined>('')
  const filters = reactive(options.filters)

  const { loader } = options

  const params: UseListOptionsResult<T, F>['params'] = {
    pagination,
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
      await loader({ ...params, signal: abortController.signal })
    } finally {
      loading.value = false
      isPaginationWatchPaused = false
    }
  }

  const loadDebounced = debounce(load, 400)

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
    loader: async ({ signal }) => {
      await repository
        .list({
          data: {
            filters: toRaw(list.params.filters),
          },
          pagination: list.params.pagination,
          signal,
        })
        .then((result) => {
          list.items.value = result.data
          list.params.pagination?.applyMeta(result.meta)
        })
    },
  })

  return {
    ...list,
    repository,
  }
}
