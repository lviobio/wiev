<template>
  <div class="flex max-w-md flex-col gap-4">
    <p>Main page!</p>
    <div>
      <NButton @click="test">Test</NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PageExpose } from '@/core/types'

const router = useRouter()

console.log('main page', router)
const test = () => {
  router.push({
    name: 'posts.index',
    windowed: {
      title: 'Posts',
    },
  })
}

defineExpose<PageExpose>({
  title: 'Home',
  breadcrumbs: [],
})
</script>
