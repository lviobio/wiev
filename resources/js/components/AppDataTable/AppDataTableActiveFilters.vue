<script setup lang="ts">
import { ActiveFilter } from '@/components/AppDataTable/useAbstractTableFilters'

defineProps<{ items: Array<ActiveFilter> }>()
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <span class="font-bold">Active filters</span>
    <template v-for="(filter, index) in items" :key="`${filter.key}-${index}`">
      <NTag size="small" closable @close="filter.remove()">
        {{ filter.indicator.label }}
      </NTag>
    </template>
  </div>
</template>

<style scoped></style>
