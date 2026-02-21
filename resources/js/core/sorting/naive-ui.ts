import { SortField, SortingComposable, SortOrder } from '@/core/sorting/base'
import { DataTableSortState } from 'naive-ui'

type NaiveSortOrder = 'ascend' | 'descend' | false

function toNaiveOrder(order: SortOrder): NaiveSortOrder {
  return order === 'asc' ? 'ascend' : 'descend'
}

function fromNaiveOrder(order: NaiveSortOrder): SortOrder | null {
  if (order === 'ascend') return 'asc'
  if (order === 'descend') return 'desc'
  return null
}

export function useNaiveUiSorting(sorting: SortingComposable) {
  function getSortOrder(columnKey: string): NaiveSortOrder {
    const found = sorting.state.value.find((s) => s.field === columnKey)
    return found ? toNaiveOrder(found.order) : false
  }

  function onUpdateSorter(sortState: DataTableSortState | DataTableSortState[] | null) {
    if (sortState === null) {
      sorting.resetSort()
      return
    }

    if (Array.isArray(sortState)) {
      const fields: SortField[] = sortState
        .filter((s) => s.order !== false)
        .map((s) => ({
          field: String(s.columnKey),
          order: fromNaiveOrder(s.order)!,
        }))
      sorting.state.value = fields
    } else {
      const order = fromNaiveOrder(sortState.order)
      if (order) {
        sorting.setSort(String(sortState.columnKey), order)
      } else {
        sorting.resetSort()
      }
    }
  }

  return { getSortOrder, onUpdateSorter }
}
