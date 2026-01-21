<template>
  <div class="flex max-w-md flex-col gap-4">
    <p>Main page! {{ router.contextKey }}</p>
    <div>
      <NButton @click="test">Test</NButton>
    </div>
    <NCard>
      <MultiRouterContext type="window" name="ctx1" location="/app/posts">
        <PageTestSub />
      </MultiRouterContext>
    </NCard>
    <!--    <NCard>-->
    <!--      <MultiRouterContext type="window" name="ctx2">-->
    <!--        <AppTestContext />-->
    <!--      </MultiRouterContext>-->
    <!--    </NCard>-->
  </div>
</template>

<script setup lang="ts">
import MultiRouterContext from '@/core/multi-router/components/MultiRouterContext.vue'
import { PageExpose } from '@/core/types'
import PageTestSub from '@/pages/PageTestSub.vue'

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
