<script setup lang="ts">
import { PageExpose } from '@/core/types'

defineExpose<PageExpose>({
  title: 'Posts',
  breadcrumbs: [
    {
      title: 'Posts',
      route: { name: 'posts.index' },
    },
  ],
})
</script>

<template>
  <NCard title="Posts">
    <RouterView v-slot="{ Component }">
      <component :is="Component" />
    </RouterView>
  </NCard>
</template>

<style scoped></style>
