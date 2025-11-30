<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { postRouteGenerator } from '@/modules/post/router/names'
import { Create } from '@/modules/post/ui/components'

const router = useRouter()
const repository = usePostRepository()

function handleCreated(result: Awaited<ReturnType<typeof repository.create>>) {
  router.push(postRouteGenerator.show(result.data.id))
}
</script>

<template>
  <NFlex>
    <Create.Component :repository="repository" @created="handleCreated" />
  </NFlex>
</template>

<style scoped></style>
