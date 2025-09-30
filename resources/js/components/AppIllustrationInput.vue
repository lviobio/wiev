<template>
  <NUpload
    ref="upload"
    :file-list="imageAsArray"
    :default-upload="false"
    :multiple="false"
    list-type="image-card"
    :max="1"
    accept="image/*"
    @update:file-list="updateImage"
  />
</template>

<script setup lang="ts">
import { UploadFileInfo } from 'naive-ui'
import { computed } from 'vue'

const image = defineModel<UploadFileInfo | undefined>('image', { required: true })

const imageAsArray = computed(() => {
  if (image.value) {
    return [image.value]
  }
  return []
})

const updateImage = (value: UploadFileInfo[]) => {
  if (value[0]) {
    image.value = value[0]
  } else {
    image.value = undefined
  }
}
</script>

<style scoped></style>
