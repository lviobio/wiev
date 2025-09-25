<script setup lang="ts">
import { PageExpose } from '@/core/types'
import { postRouteGenerator, postRouteNames } from '@/modules/post/routes'

const route = useRoute()

defineExpose<PageExpose>({
  title: 'Posts',
  breadcrumbs: [
    {
      title: 'Posts',
      route: postRouteGenerator.index(),
    },
  ],
})
</script>

<template>
  <NCard title="Posts">
    <template #header-extra>
      <RouterLink :to="postRouteGenerator.create()" v-if="route.name === postRouteNames.index">
        <NButton type="primary">Create</NButton>
      </RouterLink>
    </template>
    <RouterView v-slot="{ Component }">
      <component :is="Component" />
    </RouterView>
  </NCard>
</template>

<style scoped></style>
