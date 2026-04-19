import { createIndicator } from '@/components/AppDataTable/filters/base'
import type { ActiveFilter } from '@/components/AppDataTable/useAbstractTableFilters'
import type { ContextSyncChannel } from '@/core/list-context/useListContextSync'
import { useListContextSync } from '@/core/list-context/useListContextSync'
import { useNaiveUiCursorPagination, useNaiveUiPagePagination } from '@/core/pagination/naive-ui'
import { useNaiveUiSorting } from '@/core/sorting/naive-ui'
import { hasFilters, hasPagination, hasSearch, hasSorting } from './features'
import type { ListContextProvider } from './types'

export interface UseListAdaptersOptions {
  features: Record<string, unknown>
  context?: ListContextProvider
  /** Context-sync channels collected from feature install results. */
  contextSyncChannels?: ContextSyncChannel[]
}

export function useListAdapters(options: UseListAdaptersOptions) {
  const { features, context, contextSyncChannels = [] } = options

  // ── Feature extraction (for UI-layer adapters) ────────────────

  const pagination = hasPagination(features) ? features.pagination : undefined
  const sorting = hasSorting(features) ? features.sorting : undefined
  const search = hasSearch(features) ? features.search : undefined
  const filters = hasFilters(features) ? features.filters : undefined

  // ── Context sync (driven by feature-supplied channels) ────────

  if (context && contextSyncChannels.length) {
    useListContextSync(context.get(), contextSyncChannels)
  }

  // ── Naive UI pagination adapters ──────────────────────────────

  const dataTablePagePagination = pagination ? useNaiveUiPagePagination(pagination) : ref()
  const dataTableCursorPagination = pagination ? useNaiveUiCursorPagination(pagination) : ref()

  // ── Naive UI sorting adapter ──────────────────────────────────

  const { getSortOrder, onUpdateSorter } = sorting
    ? useNaiveUiSorting(sorting)
    : { getSortOrder: () => false as const, onUpdateSorter: () => {} }

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
    filters,
    dataTablePagination: computed(
      () => dataTablePagePagination.value || dataTableCursorPagination.value,
    ),
    getSortOrder,
    onUpdateSorter,
    searchActiveFilters,
  }
}
