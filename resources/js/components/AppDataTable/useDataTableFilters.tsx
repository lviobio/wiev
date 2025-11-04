import AppDataTableFilterMenu from '@/components/AppDataTable/AppDataTableFilterMenu.vue'
import AppDataTableFiltersFunnel from '@/components/AppDataTable/AppDataTableFiltersFunnel.vue'
import {
  FiltersState,
  getFilterRef,
  getRemovedFilterValue,
  makeFilterController,
  TableFiltering,
} from '@/components/AppDataTable/filters'
import {
  type ActiveFilter,
  useAbstractTableFilters,
} from '@/components/AppDataTable/useAbstractTableFilters'
import { DataTableInst } from 'naive-ui'
import { FilterState, TableBaseColumn, TableColumn } from 'naive-ui/es/data-table/src/interface'
import { h, type Ref, type ShallowRef } from 'vue'

export type RenderFiltersFunnelContent = { close: () => void }

export type { ActiveFilter }

type CustomizedColumnForFilters<T> = Partial<
  Pick<TableBaseColumn<T>, 'renderFilterMenu' | 'filter'>
>

function isBaseColumn<RowData>(column: TableColumn<RowData>): column is TableBaseColumn<RowData> {
  return !('type' in column)
}

export function useDataTableFilters<RowData, FS extends FiltersState>({
  filtering,
  dataTableRef,
}: {
  filtering: Ref<TableFiltering<FS> | undefined>
  dataTableRef: ShallowRef<DataTableInst | undefined>
}) {
  const { activeFilters, activeFilterKeys } = useAbstractTableFilters<FS>({
    filtering,
    getFilterRef: <K extends keyof FS & string>(filter: any, state: Ref<FS>) => {
      return getFilterRef(filter, state) as Ref<FS[K]>
    },
    getRemovedFilterValue: <K extends keyof FS & string>(filter: any, state: FS) => {
      return getRemovedFilterValue(filter, state) as FS[K]
    },
  })

  watchEffect(() =>
    dataTableRef.value?.filter(
      activeFilterKeys.value.reduce((acc, key) => {
        acc[key] = 1
        return acc
      }, {} as FilterState),
    ),
  )

  function customizeColumnForFilters(
    column: TableBaseColumn<RowData>,
  ): CustomizedColumnForFilters<RowData>
  function customizeColumnForFilters(column: TableColumn<RowData>): Record<string, never>
  function customizeColumnForFilters(column: TableColumn<RowData>) {
    if (!isBaseColumn(column)) {
      return {}
    }

    const columnKey = String(column.key)

    const filter = filtering.value?.items?.find((filter) => filter.key === columnKey)

    if (!filter) {
      return {}
    }

    const state = filtering.value!.state

    const renderFilterMenu = ({ hide }: { hide: () => void }) => {
      const controller = makeFilterController({
        filter,
        state,
        after: hide,
        autofocus: true,
      })

      return h(
        AppDataTableFilterMenu,
        {
          onClear: controller.remove,
          onConfirm: controller.confirm,
        },
        {
          default: controller.render,
        },
      )
    }

    return { renderFilterMenu }
  }

  const renderFiltersFunnelContent = (options: RenderFiltersFunnelContent) => {
    if (!filtering.value) return null

    return (
      <AppDataTableFiltersFunnel
        filtering={filtering.value}
        onApply={options.close}
        onClear={options.close}
      />
    )
  }

  return {
    customizeColumnForFilters,
    activeFilters,
    renderFiltersFunnelContent,
  }
}
