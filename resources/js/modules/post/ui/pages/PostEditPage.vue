<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { postRouteNames } from '@/modules/post/router/names'
import { Edit } from '@/modules/post/ui/components'
import { postEditPageSchema as propsSchema, PostEditPageSchema as PropsSchema } from './schemas'

const repository = usePostRepository()

const _ = defineProps<{ params: PropsSchema }>()
const props = propsSchema.parse(_.params)

const router = useRouter()
const route = useRoute()

const onUpdated = () =>
  router.push({
    name: postRouteNames.show,
    params: { id: props.id },
    title: `Post #${props.id}`,
  })
</script>

<template>
  <p>route: {{ route.name }}</p>
  <p>router.currentRoute: {{ router.currentRoute.value.name }}</p>
  <p>$route: {{ $route.name }}</p>
  <p>$router.currentRoute: {{ $router.currentRoute.value.name }}</p>
  <NFlex>
    <Edit.Component :repository="repository" :id="props.id" @updated="onUpdated" />
  </NFlex>
</template>

<style scoped></style>
