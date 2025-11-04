<script setup lang="ts">
import { RenderFiltersFunnelContent } from '@/components/AppDataTable/useDataTableFilters'
import { Filter28Filled } from '@vicons/fluent'
import { VNodeChild } from 'vue'

defineProps<{
  activeFiltersCount: number
  renderContent: (options: RenderFiltersFunnelContent) => VNodeChild
}>()

const showPopover = ref(false)

const close = () => {
  showPopover.value = false
}
</script>

<template>
  <NPopover v-model:show="showPopover" trigger="click" placement="bottom-end">
    <template #trigger>
      <NBadge :value="activeFiltersCount" type="info">
        <NButton quaternary circle>
          <template #icon>
            <NIcon>
              <Filter28Filled />
            </NIcon>
          </template>
        </NButton>
      </NBadge>
    </template>
    <component :is="renderContent({ close })" />
  </NPopover>
</template>

<style scoped></style>
