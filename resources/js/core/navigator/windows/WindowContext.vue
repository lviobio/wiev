<script setup lang="ts">
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import { provide } from 'vue'
const props = defineProps<{ keyVal: number }>()

provide(DesktopWindowKeySymbol, props.keyVal)

const root = useTemplateRef<HTMLDivElement>('root')
</script>

<template>
  <div ref="root" class="window-context-root">
    <NMessageProvider :to="root ?? undefined" container-class="!absolute">
      <slot />
    </NMessageProvider>
  </div>
</template>
