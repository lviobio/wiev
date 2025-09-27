<script setup lang="ts">
import { PageExpose } from '@/core/types'
import { useDesktopWindows } from '@/core/windows/useDesktopWindows'
import { postRouteGenerator, postRouteNames } from '@/modules/post/routes'
import { List } from '@/modules/post/ui'

const route = useRoute() // TODO: replace with appNavigator.getCurrentPage()
const windows = useDesktopWindows()

defineExpose<PageExpose>({
  title: 'Posts',
  breadcrumbs: [
    {
      title: 'Posts',
      route: postRouteGenerator.index(),
    },
  ],
})

const handleOpenListInNewWindow = windows.openable({
  title: `Posts`,
  component: List.Page,
  width: 600,
})
</script>

<template>
  <NCard title="Posts">
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
