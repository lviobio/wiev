<script setup lang="ts">
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import { provide } from 'vue'
const props = defineProps<{ keyVal: number }>()

provide(DesktopWindowKeySymbol, props.keyVal)
</script>

<template>
  <slot />
</template>
