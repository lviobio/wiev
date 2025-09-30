<script setup lang="tsx">
import { UploadFileInfo } from 'naive-ui'

export interface PostCreateFormData {
  title: string
  content: string
  cover?: UploadFileInfo
}

const model = defineModel<PostCreateFormData>({ required: true })
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
