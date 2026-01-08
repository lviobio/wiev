<script setup lang="ts">
const root = useTemplateRef<HTMLDivElement>('root')
</script>

<template>
  <div ref="root" class="window-context-root">
    <NMessageProvider :to="root ?? undefined" container-class="!absolute">
      <slot />
    </NMessageProvider>
  </div>
</template>
