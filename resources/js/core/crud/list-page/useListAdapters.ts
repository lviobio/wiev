import { makeDataTableFiltering, type TableFiltering } from '@/components/AppDataTable/filters'
import type { TableFilter } from '@/components/AppDataTable/filters/base'
import { createIndicator } from '@/components/AppDataTable/filters/base'
import type { ActiveFilter } from '@/components/AppDataTable/useAbstractTableFilters'
import { useListContextSync } from '@/core/list-context/useListContextSync'
import { useNaiveUiCursorPagination, useNaiveUiPagePagination } from '@/core/pagination/naive-ui'
import { useNaiveUiSorting } from '@/core/sorting/naive-ui'
import type { Reactive } from 'vue'
import type { z } from 'zod'
import { hasPagination, hasSearch, hasSorting } from './features'
import { defineFilters } from './filters'
import type { ListContextProvider } from './types'

export interface UseListAdaptersOptions<
  FST extends Record<string, unknown>,
  FS extends z.ZodObject,
> {
  features: Record<string, unknown>
  filters: Reactive<FST>
  filtersSchema: FS
  filterItems?: TableFilter[]
  context?: ListContextProvider
}

export function useListAdapters<FST extends Record<string, unknown>, FS extends z.ZodObject>(
  options: UseListAdaptersOptions<FST, FS>,
) {
  const { features, filters, filtersSchema, filterItems, context } = options

  // ── Feature extraction ────────────────────────────────────────

  const pagination = hasPagination(features) ? features.pagination : undefined
  const sorting = hasSorting(features) ? features.sorting : undefined
  const search = hasSearch(features) ? features.search : undefined

  // ── Context sync ──────────────────────────────────────────────

  if (context) {
    useListContextSync(context.get(), {
      filters,
      pagination,
      sorting,
      search,
    })
  }

  // ── Naive UI pagination adapters ──────────────────────────────

  const dataTablePagePagination = pagination ? useNaiveUiPagePagination(pagination) : ref()
  const dataTableCursorPagination = pagination ? useNaiveUiCursorPagination(pagination) : ref()

  // ── Naive UI sorting adapter ──────────────────────────────────

  const { getSortOrder, onUpdateSorter } = sorting
    ? useNaiveUiSorting(sorting)
    : { getSortOrder: () => false as const, onUpdateSorter: () => {} }

  // ── Filter integration ────────────────────────────────────────

  const resolvedFilterItems = filterItems ?? defineFilters(filtersSchema)

  const filtering: TableFiltering | undefined = resolvedFilterItems.length
    ? makeDataTableFiltering(toRef(filters) as any, resolvedFilterItems as any)
    : undefined

  // ── Search active filter indicator ────────────────────────────

  const searchActiveFilters = computed<ActiveFilter[]>(() => {
    if (!search?.value) return []

    return [
      {
        key: 'search',
        indicator: createIndicator(`Search: ${search.value}`),
        remove: () => {
          search.value = undefined
        },
      },
    ]
  })

  return {
    pagination,
    sorting,
    search,
    dataTablePagination: computed(
      () => dataTablePagePagination.value || dataTableCursorPagination.value,
    ),
    getSortOrder,
    onUpdateSorter,
    filtering,
    searchActiveFilters,
  }
}
