import type { DefaultListQueryContract } from '@/core/api/simple-repository-helpers-v1/main'
import type { MaybePaginatedData } from '@/core/pagination/base'
import { toRaw } from 'vue'
import type { DataLoader, DataLoaderParams, DefaultListFeaturesState } from './types'

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
  listFn: (query: DefaultListQueryContract<{ filter?: F }>) => Promise<MaybePaginatedData<T>>,
): DataLoader<T, DefaultListFeaturesState<F>> {
  return async ({ features, signal }: DataLoaderParams<DefaultListFeaturesState<F>>) => {
    const { filters, search, pagination, sorting } = features

    return listFn({
      data: {
        filter: {
          ...(toRaw(filters) as F),
          ...(search?.value ? { search: search.value } : {}),
        },
      },
      pagination,
      sorting,
      signal,
    })
  }
}
