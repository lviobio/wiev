<script setup lang="tsx">
import { useForwardRef } from '@/core/utils/useForwardRef'
import { pick } from 'lodash'
import { DataTableInst, DataTableProps, dataTableProps, NDataTable } from 'naive-ui'
import { FilterState } from 'naive-ui/es/data-table/src/interface'
import { ComponentSlots } from 'vue-component-type-helpers'

defineOptions({
  inheritAttrs: false,
})

/**
 * Vue-ignore is used to ignore the error that is thrown by the TypeScript compiler
 * @see {https://github.com/tusen-ai/naive-ui/issues/4810}
 */
export interface AppDataTableProps extends /* @vue-ignore */ DataTableProps {
  loading: boolean
  appliedFilters?: string[]
  load?: () => Promise<void>
}

const props = defineProps<AppDataTableProps>()
const attrs = useAttrs()
const forwarded = computed(() => ({ ...pick(props, Object.keys(dataTableProps)), ...attrs }))

const [elRef, forwardRef] = useForwardRef<DataTableInst>()

const slots = defineSlots<ComponentSlots<typeof NDataTable>>()

watch(
  () => props.appliedFilters,
  (value) => {
    if (!value) return

    const filterState: FilterState = {}

    value.forEach((key) => {
      filterState[key] = 1
    })

    elRef.value?.filter(filterState)
  },
)

const typedSlot = (name: string | number) => name as keyof typeof slots
</script>

<template>
  <NDataTable :ref="forwardRef" v-bind="forwarded">
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
</template>

<style scoped></style>
