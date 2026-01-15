<template>
  <div class="flex max-w-md flex-col gap-4">
    <p>Main page!</p>
    <div>
      <NButton @click="test">Test</NButton>
    </div>
    <NCard>
      <SubRouterContext context-key="ctx1">
        <AppTestContext />
      </SubRouterContext>
    </NCard>
    <NCard>
      <SubRouterContext context-key="ctx2">
        <AppTestContext />
      </SubRouterContext>
    </NCard>
  </div>
</template>

<script setup lang="ts">
import AppTestContext from '@/components/AppTestContext.vue'
import SubRouterContext from '@/core/sub-router/components/SubRouterContext.vue'
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
