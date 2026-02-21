import { PaginationComposable } from '@/core/pagination/base'
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core'
import { PaginationProps } from 'naive-ui'
import { computed, ComputedRef } from 'vue'

export interface NaiveUiPaginationOptions {
  pageSizes?: number[]
  showSizePicker?: boolean
}

export const useNaiveUiPagination = (
  pagination: PaginationComposable,
  options?: NaiveUiPaginationOptions,
): ComputedRef<PaginationProps | false> => {
  const breakpoints = useBreakpoints(breakpointsTailwind)
  const md = breakpoints.smaller('md')

  const pageSizes = options?.pageSizes ?? [15, 25, 50, 100]
  const showSizePicker = options?.showSizePicker ?? true

  return computed(() => {
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

      onChange: (page: number) => pagination.setPage(page),
      onUpdatePageSize: (pageSize: number) => pagination.setPerPage(pageSize),
    } satisfies PaginationProps
  })
}
