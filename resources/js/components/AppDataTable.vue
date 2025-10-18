<script setup lang="ts" generic="D extends Record<string, any>">
import AppDataTableFilterMenu from '@/components/AppDataTableFilterMenu.vue'
import { useForwardRef } from '@/core/utils/useForwardRef'
import { Filter28Filled } from '@vicons/fluent'
import { pick } from 'lodash'
import {
  DataTableColumns,
  DataTableInst,
  DataTableProps,
  dataTableProps,
  NButton,
  NDataTable,
} from 'naive-ui'
import { FilterState, TableBaseColumn } from 'naive-ui/es/data-table/src/interface'
import { ComputedRef, VNodeChild } from 'vue'
import { ComponentSlots } from 'vue-component-type-helpers'

export interface TableFilter {
  key: string
  title: string
  getIndicator: () => string
  active: ComputedRef<boolean>
  reset(): void
  make({ after }: { after: () => void }): {
    input: () => VNodeChild
    confirm: () => void
    clear: () => void
  }
}

/**
 * Vue-ignore is used to ignore the error that is thrown by the TypeScript compiler
 * @see {https://github.com/tusen-ai/naive-ui/issues/4810}
 */
export interface AppDataTableProps<RowData> extends /* @vue-ignore */ DataTableProps {
  loading: boolean
  columns: DataTableColumns<RowData>
  filters?: TableFilter[]
  load?: () => Promise<void>
}

defineOptions({
  inheritAttrs: false,
})

const customizedOriginalProps = computed(() => {
  return {
    columns: props.columns?.map((column) => {
      const result: TableBaseColumn = {
        ...(column as TableBaseColumn),
      }
      const tableFilter = props.filters?.find((filter) => filter.key === result.key)
      if (tableFilter) {
        result.renderFilterMenu = ({ hide }) => {
          const controller = tableFilter.make({ after: hide })

          return h(
            AppDataTableFilterMenu,
            {
              onClear: controller.clear,
              onConfirm: controller.confirm,
            },
            {
              default: () => controller.input(),
            },
          )
        }
      }
      return result
    }),
  }
})

const props = defineProps<AppDataTableProps<D>>()
const attrs = useAttrs()
const forwarded = computed(() => ({
  ...pick(props, Object.keys(dataTableProps)),
  ...customizedOriginalProps.value,
  ...attrs,
}))

const [elRef, forwardRef] = useForwardRef<DataTableInst>()

const slots = defineSlots<
  ComponentSlots<typeof NDataTable> & {
    header(): VNodeChild
  }
>()

const activeFilterKeys = computed(() => {
  const result: string[] = []

  props.filters?.forEach((filter) => {
    if (filter.active.value) {
      result.push(filter.key)
    }
  })

  return result
})

watch(activeFilterKeys, (activeList) => {
  const filterState: FilterState = {}
  activeList.forEach((key) => (filterState[key] = 1))
  elRef.value?.filter(filterState)
})

const activeFilters = computed(() => {
  const result: {
    key: string
    getIndicator(): string
  }[] = []

  props.filters?.forEach((filter) => {
    if (!activeFilterKeys.value.includes(filter.key)) {
      return
    }

    result.push({
      key: filter.key,
      getIndicator: filter.getIndicator,
    })
  })

  return result
})

function removeFilter(key: string) {
  props.filters?.forEach((filter) => {
    if (filter.key === key) {
      filter.reset()
    }
  })
}

const typedSlot = (name: string | number) => name as keyof typeof slots

const showFiltersFunnel = ref(false)
</script>

<template>
  <NTable striped bordered>
    <thead>
      <tr>
        <td>
          <div class="flex justify-between">
            <div>
              <slot name="header" />
            </div>
            <div v-if="props.filters?.length" class="self-end">
              <NPopover trigger="click" v-model:show="showFiltersFunnel" placement="bottom-end">
                <template #trigger>
                  <NBadge :value="activeFilters.length" type="info">
                    <NButton quaternary circle>
                      <template #icon>
                        <NIcon>
                          <Filter28Filled />
                        </NIcon>
                      </template>
                    </NButton>
                  </NBadge>
                </template>
                <AppDataTableFiltersFunnel
                  @apply="showFiltersFunnel = false"
                  @reset="showFiltersFunnel = false"
                  :filters="props.filters"
                />
              </NPopover>
            </div>
          </div>
        </td>
      </tr>
      <tr v-if="activeFilters.length">
        <td>
          <div class="flex flex-wrap items-center gap-2">
            <span>Active filters</span>
            <template v-for="filter in activeFilters" :key="filter">
              <NTag size="small" closable @close="removeFilter(filter.key)">
                {{ filter.getIndicator() }}
              </NTag>
            </template>
          </div>
        </td>
      </tr>
      <tr></tr>
    </thead>
    <tbody>
      <NDataTable :ref="forwardRef" v-bind="forwarded" :bordered="false">
        <template v-for="(_, slot) in $slots" #[slot]="scope">
          <slot :name="typedSlot(slot)" v-bind="scope || {}" />
        </template>
        <template #empty v-if="!slots.empty">
          <NEmpty size="large">
            <div class="flex flex-col items-center justify-center gap-1">
              <p>No data</p>
              <NButton size="small">Reload</NButton>
            </div>
          </NEmpty>
        </template>
      </NDataTable>
    </tbody>
  </NTable>
</template>

<style scoped>
:deep(.n-data-table-wrapper) {
  border-top-left-radius: 0 !important;
  border-top-right-radius: 0 !important;
}
:deep(.n-data-table__pagination) {
  padding-bottom: 12px;
  padding-right: 16px;
}
</style>
