<script setup lang="ts" generic="D extends Record<string, any>, FS extends Record<string, unknown>">
import AppDataTableActiveFilters from '@/components/AppDataTable/AppDataTableActiveFilters.vue'
import AppDataTableFiltersFunnelButton from '@/components/AppDataTable/AppDataTableFiltersFunnelButton.vue'
import { TableFiltering } from '@/components/AppDataTable/filters'
import { useDataTableFilters } from '@/components/AppDataTable/useDataTableFilters'
import { useForwardRef } from '@/core/utils/useForwardRef'
import { pick } from 'lodash'
import {
  DataTableColumns,
  DataTableInst,
  DataTableProps,
  dataTableProps,
  NDataTable,
} from 'naive-ui'
import { VNodeChild } from 'vue'
import { ComponentSlots } from 'vue-component-type-helpers'

/**
 * Vue-ignore is used to ignore the error that is thrown by the TypeScript compiler
 * @see {https://github.com/tusen-ai/naive-ui/issues/4810}
 */
export interface AppDataTableProps<RowData, FS extends Record<string, unknown>>
  extends /* @vue-ignore */ DataTableProps {
  loading: boolean
  loader?: () => Promise<void> | undefined
  columns: DataTableColumns<RowData>
  filtering?: TableFiltering<FS>
}

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<AppDataTableProps<D, FS>>()
const attrs = useAttrs()

const [elRef, forwardRef] = useForwardRef<DataTableInst>()

const { customizeColumnForFilters, activeFilters, renderFiltersFunnelContent } =
  useDataTableFilters<D, FS>({
    filtering: toRef(props, 'filtering'),
    dataTableRef: elRef,
  })

const forwarded = computed(() => ({
  ...pick(props, Object.keys(dataTableProps)),
  columns: props.columns?.map((column) => {
    return {
      ...column,
      ...customizeColumnForFilters(column),
    }
  }),
  ...attrs,
}))

const slots = defineSlots<
  ComponentSlots<typeof NDataTable> & {
    header(): VNodeChild
  }
>()

const typedSlot = (name: string | number) => name as keyof typeof slots

onMounted(() => {
  props.loader?.()
})
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
            <div v-if="props.filtering?.items.length" class="self-end">
              <AppDataTableFiltersFunnelButton
                :active-filters-count="activeFilters.length"
                :render-content="renderFiltersFunnelContent"
              />
            </div>
          </div>
        </td>
      </tr>
      <tr v-if="activeFilters.length">
        <td>
          <AppDataTableActiveFilters :items="activeFilters" />
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
              <NButton size="small" v-if="props.loader" @click="props.loader">Reload</NButton>
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
