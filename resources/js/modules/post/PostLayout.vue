<script setup lang="ts">
import { PageExpose } from '@/core/types'
import { postRouteGenerator, postRouteNames } from '@/modules/post/router/names'

const router = useRouter()
const route = useRoute() // TODO: replace with appNavigator.getCurrentPage()

defineExpose<PageExpose>({
  title: 'Posts',
  breadcrumbs: [
    {
      title: 'Posts',
      route: postRouteGenerator.index(),
    },
  ],
})

const handleOpenListInNewWindow = () =>
  router.push({
    name: postRouteNames.index,
    windowed: {
      title: 'Posts',
    },
  })
</script>

<template>
  <NCard title="Posts">
    PostLayout
    <template #header-extra>
      <NFlex>
        <NButton v-if="route.name === postRouteNames.index" @click="handleOpenListInNewWindow">
          Open list in new window
        </NButton>
        <RouterLink :to="postRouteGenerator.create()" v-if="route.name === postRouteNames.index">
          <NButton type="primary">Create</NButton>
        </RouterLink>
      </NFlex>
    </template>
    <RouterView v-slot="{ Component }">
      <component :is="Component" />
    </RouterView>
  </NCard>
</template>

<style scoped></style>
