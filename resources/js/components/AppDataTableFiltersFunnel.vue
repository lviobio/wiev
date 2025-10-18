<script setup lang="ts">
import { TableFilter } from '@/components/AppDataTable.vue'
import { NButton } from 'naive-ui'

const props = defineProps<{
  filters: TableFilter[]
}>()

const emit = defineEmits<{
  apply: []
  reset: []
}>()

const builtFilters = ref<
  {
    filter: TableFilter
    controller: ReturnType<TableFilter['make']>
  }[]
>([])

watchEffect(() => {
  builtFilters.value = props.filters.map((filter) => ({
    filter,
    controller: filter.make({ after: () => {} }),
  }))
})

function handleApply() {
  builtFilters.value.forEach((data) => {
    data.controller.confirm()
  })

  emit('apply')
}

function handleReset() {
  builtFilters.value.forEach((data) => {
    data.controller.clear()
  })

  emit('reset')
}
</script>

<template>
  <div class="flex min-w-32 flex-col gap-2">
    <div class="flex items-center justify-between">
      <span class="text-lg">Filters</span>
      <NButton size="small" quaternary type="error" @click="handleReset">Clear</NButton>
    </div>
    <div class="flex flex-col gap-2">
      <template v-for="data in builtFilters" :key="data.filter.key">
        <NFormItem :label="data.filter.title" :show-feedback="false">
          <component :is="data.controller.input()" />
        </NFormItem>
      </template>
    </div>
    <div>
      <NButton type="primary" @click="handleApply">Apply filters</NButton>
    </div>
  </div>
</template>
