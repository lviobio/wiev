import { PaginationComposable } from '@/core/pagination/base'
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core'
import { PaginationProps } from 'naive-ui'
import { computed, ComputedRef } from 'vue'

export const useNaiveUiPagination = (
  pagination: PaginationComposable,
  pageSizes: number[] | false = [15, 25, 50, 100],
): ComputedRef<PaginationProps> => {
  const breakpoints = useBreakpoints(breakpointsTailwind)
  const md = breakpoints.smaller('md')

  return computed(() => {
    const showSizePicker = pagination.perPageAvailable && pageSizes !== false
    const pageSizeList = pagination.perPageAvailable && pageSizes !== false ? pageSizes : []

    return {
      page: pagination.meta.current_page,
      itemCount: pagination.meta.total,
      pageSize: pagination.meta.per_page,
      simple: md.value,
      showSizePicker,
      pageSizes: pageSizeList,

      prefix(info) {
        return `Total: ${info.itemCount}`
      },

      onChange: (page: number) => pagination.setPage(page),
      onUpdatePageSize: (pageSize: number) => {
        pagination.setPerPage(pageSize)
      },
    } satisfies PaginationProps
  })
}
