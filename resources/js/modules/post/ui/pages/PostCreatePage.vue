<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { Create } from '@/modules/post/ui'

const repository = usePostRepository()
</script>

<template>
  <NFlex>
    <Create.Component :repository="repository" />
  </NFlex>
</template>

<style scoped></style>
