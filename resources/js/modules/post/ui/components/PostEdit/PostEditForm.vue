<script setup lang="tsx">
import type { PostEditFormData } from '@/modules/post/ui/components/PostEdit/form'

const model = defineModel<PostEditFormData>({ required: true })
</script>

<template>
  <NFlex vertical>
    <NFormItem label="Title" path="title">
      <NInput v-model:value="model.title" />
    </NFormItem>
    <NFormItem label="Content" path="content">
      <NInput v-model:value="model.content" />
    </NFormItem>
    <NFormItem label="Cover" path="cover">
      <AppIllustrationInput v-model:image="model.cover" />
    </NFormItem>
  </NFlex>
</template>

<style scoped></style>
