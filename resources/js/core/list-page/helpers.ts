import type { MaybePaginatedData } from '@/core/pagination/base'
import { toRaw } from 'vue'
import type { DataLoader } from './types'

/**
 * Repository contract expected by makeDataHandlerFromRepository.
 * The repository must have a `list()` method accepting an object with
 * `data`, `pagination`, `sorting` and `signal` fields.
 */
interface ListableRepository<T, F> {
  list(query: {
    data: { filters: F; search?: string }
    pagination?: any
    sorting?: any
    signal?: AbortSignal
  }): Promise<MaybePaginatedData<T>>
}

/**
 * Creates a DataLoader from a repository that follows the standard
 * list query contract (DefaultListQueryContract pattern).
 *
 * Search is sent as a top-level `data.search` field (not inside filters),
 * matching the backend convention where `search` is a standalone parameter.
 *
 * @example
 * ```ts
 * const repository = usePostRepository()
 * const dataHandler = makeDataHandlerFromRepository(repository)
 * ```
 */
export function makeDataHandlerFromRepository<T, F extends Record<string, unknown>>(
  repository: ListableRepository<T, F>,
): DataLoader<T, F> {
  return async ({ filters, pagination, sorting, search, signal }) => {
    return repository.list({
      data: {
        filters: toRaw(filters) as F,
        ...(search ? { search } : {}),
      },
      pagination,
      sorting,
      signal,
    })
  }
}
