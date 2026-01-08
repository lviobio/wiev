<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { Show } from '@/modules/post/ui/components'
import { postShowPageSchema as propsSchema, PostShowPageSchema as PropsSchema } from './schemas'

const repository = usePostRepository()

const _ = defineProps<{ params: PropsSchema }>()
console.log('_', { _ }, useRoute())
const props = propsSchema.parse(_.params)

const route = useRoute()
const router = useRouter()
// const inst = getCurrentInstance()
// console.log(inst)
// inst.ctx.$asd = 'qwe'
const canGoBack = Boolean(window.history.state.back)
const instance = getCurrentInstance()

// Проверяем, что лежит в контексте рендеринга
console.log('Instance Proxy $route:', instance.proxy.$route)

const backDelayed = () => {
  setTimeout(() => {
    router.back()
  }, 1000)
}

console.log('router!!', router)
</script>

<template>
  <p>route: {{ route.name }}</p>
  <p>router.currentRoute: {{ router.currentRoute.value.name }}</p>
  <p>$route: {{ $route.name }}</p>
  <p>$router.currentRoute: {{ $router.currentRoute.value.name }}</p>
  <NButton @click="router.back()" :disabled="!canGoBack">back</NButton>
  <NButton @click="backDelayed" :disabled="!canGoBack">back delayed</NButton>
  <NFlex>
    <Show.Component :repository="repository" :id="props.id" />
  </NFlex>
</template>

<style scoped></style>
