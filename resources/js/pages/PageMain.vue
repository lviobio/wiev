<template>
  <div class="flex max-w-md flex-col gap-4">
    <p>Main page!</p>
    <div>
      <NButton @click="test">Test</NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAppNavigator } from '@/core/navigator/useAppNavigator'
import { PageExpose } from '@/core/types'

const appNavigator = useAppNavigator()

const test = () => {
  appNavigator.navigate({
    name: 'posts.index',
    title: 'Posts',
    windowed: true,
  })
}

defineExpose<PageExpose>({
  title: 'Home',
  breadcrumbs: [],
})
</script>
