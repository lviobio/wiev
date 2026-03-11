<script setup lang="ts">
import type { CursorPaginationProps } from '@/core/pagination/naive-ui'
import {
  ChevronDoubleLeft20Filled,
  ChevronLeft20Filled,
  ChevronRight20Filled,
} from '@vicons/fluent'
import { NButton, NFlex, NSelect } from 'naive-ui'

const props = defineProps<{
  pagination: CursorPaginationProps
}>()

const pageSizeOptions = computed(() =>
  props.pagination.pageSizes.map((size) => ({
    label: `${size} / page`,
    value: size,
  })),
)
</script>

<template>
  <NFlex :size="4">
    <NButton size="small" :disabled="!pagination.hasPrev" @click="pagination.onReset">
      <template #icon>
        <ChevronDoubleLeft20Filled />
      </template>
    </NButton>
    <NButton size="small" :disabled="!pagination.hasPrev" @click="pagination.onPrev">
      <template #icon>
        <ChevronLeft20Filled />
      </template>
      <span>Back</span>
    </NButton>
    <NButton
      size="small"
      :disabled="!pagination.hasNext"
      @click="pagination.onNext"
      icon-placement="right"
    >
      <template #icon>
        <ChevronRight20Filled />
      </template>
      <span>Next</span>
    </NButton>
  </NFlex>
  <NSelect
    v-if="pagination.showSizePicker"
    :value="pagination.pageSize"
    :options="pageSizeOptions"
    :consistent-menu-width="false"
    size="small"
    style="width: 120px"
    @update:value="pagination.onUpdatePageSize"
  />
</template>

<style scoped></style>
