<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { Show } from '@/modules/post/ui'

const repository = usePostRepository()
const route = useRoute()
</script>

<template>
  <NFlex>
    <Show.Component :repository="repository" :id="Number(route.params.id)" />
  </NFlex>
</template>

<style scoped></style>
