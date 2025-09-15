<script setup lang="tsx">
export interface PostEditFormData {
  title: string
  content: string
}

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
  </NFlex>
</template>

<style scoped></style>
