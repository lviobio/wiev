<script setup lang="ts">
import { PageExpose } from '@/core/types'

const route = useRoute()

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
    <template #header-extra>
      <RouterLink :to="{ name: 'posts.create' }" v-if="route.name !== 'posts.create'">
        <NButton type="primary">Create</NButton>
      </RouterLink>
    </template>
    <RouterView v-slot="{ Component }">
      <component :is="Component" />
    </RouterView>
  </NCard>
</template>

<style scoped></style>
