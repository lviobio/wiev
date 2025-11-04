<script setup lang="ts" generic="FS extends Record<string, unknown>">
import {
  makeFilterController,
  TableFilterController,
  TableFiltering,
} from '@/components/AppDataTable/filters'
import { NButton } from 'naive-ui'

const props = defineProps<{
  filtering: TableFiltering<FS>
}>()

const emit = defineEmits<{
  apply: []
  clear: []
}>()

const builtFilters = ref<Array<TableFilterController<keyof FS & string, FS[keyof FS & string]>>>([])

watchEffect(() => {
  builtFilters.value = props.filtering.items.map((filter, index) =>
    makeFilterController({
      filter,
      state: props.filtering.state,
      autofocus: index === 0,
    }),
  )
})

function handleApply() {
  builtFilters.value.forEach((controller) => controller.confirm())

  emit('apply')
}

function handleClear() {
  builtFilters.value.forEach((controller) => controller.remove())

  emit('clear')
}
</script>

<template>
  <div class="flex min-w-32 flex-col gap-2">
    <div class="flex items-center justify-between">
      <span class="text-lg">Filters</span>
      <NButton size="small" quaternary type="error" @click="handleClear">Clear</NButton>
    </div>
    <div class="flex flex-col gap-2">
      <template v-for="controller in builtFilters" :key="controller.filter.key">
        <NFormItem :label="controller.filter.title" :show-feedback="false">
          <component :is="controller.render()" />
        </NFormItem>
      </template>
    </div>
    <div>
      <NButton type="primary" @click="handleApply">Apply filters</NButton>
    </div>
  </div>
</template>
