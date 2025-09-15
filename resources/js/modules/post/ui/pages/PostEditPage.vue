<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { showRoute } from '@/modules/post/routes'
import { Edit } from '@/modules/post/ui'

const repository = usePostRepository()
const route = useRoute()

const id = computed(() => Number(route.params.id))
</script>

<template>
  <NFlex>
    <Edit.Component :repository="repository" :id="id" @updated="$router.push(showRoute(id))" />
  </NFlex>
</template>

<style scoped></style>
