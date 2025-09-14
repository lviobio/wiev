import { reactive, readonly } from 'vue'

export type WithPaginationData<T> = T & {
  page: number
  per_page?: number
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginationQueryParams {
  page: number
  per_page?: number
}

export interface PaginatedData<T> {
  data: T[]
  meta: PaginationMeta
}

interface UsePaginationProps {
  withPerPageSelect: boolean
  onPageChange?: (page: number) => void
}

export interface PaginationComposable {
  meta: PaginationMeta
  toQueryParams: () => PaginationQueryParams
  setPage: (page: number) => void
  setPerPage: (perPage: number) => void
  perPageAvailable: boolean
  setMeta: (newMeta: PaginationMeta) => void
}

export const useBasePagination = (options: UsePaginationProps): PaginationComposable => {
  const meta = reactive({
    current_page: 1,
    from: 0,
    last_page: 1,
    per_page: 15,
    to: 0,
    total: 0,
  })

  function toQueryParams(): PaginationQueryParams {
    return {
      page: meta.current_page,
      per_page: options.withPerPageSelect ? meta.per_page : undefined,
    }
  }

  function setPage(page: number) {
    meta.current_page = page

    options.onPageChange?.(page)
  }

  function setPerPage(perPage: number) {
    meta.per_page = perPage

    setPage(1)
  }

  return {
    meta: readonly(meta),
    toQueryParams,
    setPage,
    setPerPage,
    perPageAvailable: options.withPerPageSelect,
    setMeta: (newMeta: PaginationMeta) => {
      Object.assign(meta, newMeta)
    },
  }
}
