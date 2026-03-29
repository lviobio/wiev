import type { DefaultListQueryContract } from '@/core/api/simple-repository-helpers-v1/main'
import type { MaybePaginatedData, PaginationComposable } from '@/core/pagination/base'
import type { SortingComposable } from '@/core/sorting/base'
import { toRaw, type Reactive, type Ref } from 'vue'
import type { DataLoader, DataLoaderParams } from './types'

interface DefaultListFeatures<F extends Record<string, unknown>> {
  filters: Reactive<F>
  search: Ref<string | undefined>
  pagination: PaginationComposable
  sorting: SortingComposable
}

/**
 * Adapts a function expecting `DefaultListQueryContract` into a `DataLoader`.
 *
 * Maps `DataLoaderParams.features` (pagination, sorting, search, filters)
 * into the `{ data, pagination, sorting, signal }` shape that repositories expect.
 *
 * @example
 * ```ts
 * const repository = usePostRepository()
 * const dataHandler = makeDataHandlerFromRepositoryAdapter<Post, PostListFilters>(
 *   repository.list.bind(repository),
 * )
 * ```
 */
export function makeDataHandlerFromRepositoryAdapter<T, F extends Record<string, unknown>>(
  listFn: (
    query: DefaultListQueryContract<{ filters?: F; search?: string }>,
  ) => Promise<MaybePaginatedData<T>>,
): DataLoader<T> {
  return async ({ features, signal }: DataLoaderParams) => {
    const { filters, search, pagination, sorting } = features as unknown as DefaultListFeatures<F>

    return listFn({
      data: {
        filters: toRaw(filters) as F,
        ...(search?.value ? { search: search.value } : {}),
      },
      pagination,
      sorting,
      signal,
    })
  }
}
