import { castAsCursor, PaginationComposable } from '@/core/pagination/base'
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core'
import { PaginationProps } from 'naive-ui'
import { computed, ComputedRef } from 'vue'

// ── Shared options ──────────────────────────────────────────────

export interface NaiveUiPaginationOptions {
  pageSizes?: number[]
  showSizePicker?: boolean
}

const DEFAULT_PAGE_SIZES = [15, 25, 50, 100]

// ── Page pagination adapter ─────────────────────────────────────

export const useNaiveUiPagePagination = (
  pagination: PaginationComposable,
  options?: NaiveUiPaginationOptions,
): ComputedRef<PaginationProps | false> => {
  const breakpoints = useBreakpoints(breakpointsTailwind)
  const md = breakpoints.smaller('md')

  const pageSizes = options?.pageSizes ?? DEFAULT_PAGE_SIZES
  const showSizePicker = options?.showSizePicker ?? true

  return computed((): PaginationProps | false => {
    const state = pagination.state.value
    if (state?.type !== 'page' || !state.meta) {
      return false
    }

    return {
      page: state.page,
      itemCount: state.meta?.total,
      pageSize: state.per_page,
      simple: md.value,
      showSizePicker,
      pageSizes,

      prefix(info) {
        return `Total: ${info.itemCount}`
      },

      'onUpdate:page': (page: number) => pagination.setPage(page),
      'onUpdate:pageSize': (pageSize: number) => pagination.setPerPage(pageSize),
    }
  })
}

// ── Cursor pagination adapter ───────────────────────────────────

export interface CursorPaginationProps {
  hasNext: boolean
  hasPrev: boolean
  pageSize: number
  pageSizes: number[]
  showSizePicker: boolean
  onNext: () => void
  onPrev: () => void
  onReset: () => void
  onUpdatePageSize: (pageSize: number) => void
}

export const useNaiveUiCursorPagination = (
  pagination: PaginationComposable,
  options?: NaiveUiPaginationOptions,
): ComputedRef<CursorPaginationProps | false> => {
  const pageSizes = options?.pageSizes ?? DEFAULT_PAGE_SIZES
  const showSizePicker = options?.showSizePicker ?? true

  return computed(() => {
    const state = castAsCursor(pagination.state.value)
    if (!state?.meta) {
      return false
    }

    return {
      hasNext: state.meta.next_cursor !== null,
      hasPrev: state.meta.prev_cursor !== null,
      pageSize: state.per_page ?? state.meta.per_page,
      pageSizes,
      showSizePicker,
      onNext: () => {
        if (state.meta?.next_cursor) {
          pagination.setCursor(state.meta.next_cursor)
        }
      },
      onPrev: () => {
        if (state.meta?.prev_cursor) {
          pagination.setCursor(state.meta.prev_cursor)
        }
      },
      onReset: () => {
        pagination.setCursor(undefined)
      },
      onUpdatePageSize: (pageSize: number) => pagination.setPerPage(pageSize),
    } satisfies CursorPaginationProps
  })
}

export type NaiveUiPagination = CursorPaginationProps | PaginationProps

export function isNaiveUiPagePagination(
  pagination: NaiveUiPagination,
): pagination is PaginationProps {
  return 'page' in pagination
}

export function isNaiveUiCursorPagination(
  pagination: NaiveUiPagination,
): pagination is CursorPaginationProps {
  return 'hasNext' in pagination
}
