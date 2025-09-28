<script setup lang="ts">
import { NCard } from 'naive-ui'
import { onBeforeUnmount, ref } from 'vue'

const props = defineProps<{
  title: string
  x: number
  y: number
  z: number
  width?: number
}>()

const emit = defineEmits<{
  (e: 'update:x', v: number): void
  (e: 'update:y', v: number): void
  (e: 'focus'): void
  (e: 'close'): void
}>()

const dragging = ref<{ dx: number; dy: number } | null>(null)

function onHeaderMouseDown(e: MouseEvent) {
  emit('focus')
  dragging.value = { dx: e.clientX - props.x, dy: e.clientY - props.y }
  window.addEventListener('mousemove', onMove)
  window.addEventListener('mouseup', onUp)
}

function onMove(e: MouseEvent) {
  if (!dragging.value) return
  const { dx, dy } = dragging.value
  emit('update:x', e.clientX - dx)
  emit('update:y', e.clientY - dy)
}

function onUp() {
  window.removeEventListener('mousemove', onMove)
  window.removeEventListener('mouseup', onUp)
  dragging.value = null
}

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onMove)
  window.removeEventListener('mouseup', onUp)
})
</script>

<template>
  <div
    class="pointer-events-auto fixed shadow"
    :style="{ top: y + 'px', left: x + 'px', zIndex: z, width: (width ?? 800) + 'px' }"
    @mousedown="emit('focus')"
  >
    <NCard
      size="small"
      closable
      @close="emit('close')"
      :theme-overrides="{ paddingSmall: '2px 10px 10px' }"
    >
      <template #header>
        <div
          class="flex cursor-move items-center justify-between gap-2 p-2 select-none"
          @mousedown.prevent="onHeaderMouseDown"
        >
          <div class="font-semibold">{{ title }}</div>
        </div>
      </template>
      <slot />
    </NCard>
  </div>
</template>

<style scoped></style>
