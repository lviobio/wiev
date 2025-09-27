<script lang="ts">
export const DesktopWindowKeySymbol = Symbol('desktop-window-key')
</script>

<script setup lang="ts">
import { provide } from 'vue'
const props = defineProps<{ keyVal: number }>()

provide(DesktopWindowKeySymbol, props.keyVal)
</script>

<template>
  <slot />
</template>
