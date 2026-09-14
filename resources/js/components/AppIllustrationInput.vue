<template>
  <NUpload
    :file-list="imageAsArray"
    :custom-request="customRequest"
    :multiple="false"
    :disabled="uploading"
    list-type="image-card"
    :max="1"
    accept="image/*"
    @update:file-list="updateImage"
  />
</template>

<script setup lang="ts">
import { useChunkedUpload } from '@/core/upload/useChunkedUpload'
import { RemovableUploadFileInfo, UploadFileInfoWithReference } from '@/core/utils/form-schemas'
import { UploadCustomRequestOptions, UploadFileInfo } from 'naive-ui'
import { computed, ref } from 'vue'

const image = defineModel<RemovableUploadFileInfo | undefined>('image')

const initiallySet = Boolean(image.value)
const uploading = ref(false)

const chunkedUpload = useChunkedUpload()

// naive-ui's own onFinish handling rebuilds its file-info object from its internally
// tracked `file` (Object.assign({}, file, {status, percentage})) and re-emits
// update:file-list with that - silently discarding anything we set on image.value
// ourselves beforehand. So the temp-upload identifier can't ride inside the
// UploadFileInfo object; it's kept here instead, looked up by the stable file id
// naive-ui does preserve across that reconstruction.
const references = ref<Record<string, string>>({})

const imageAsArray = computed(() => {
  if (image.value) {
    return [image.value]
  }
  return []
})

// Removing the picked file (before the form is submitted) doesn't need to be told to
// the backend - nothing has been attached to a Post yet, the upload just sits there
// until App\Core\Upload\Console\Commands\PruneTemporaryUploadsCommand reaps it.
const updateImage = (value: UploadFileInfo[]) => {
  const file = value[0]

  if (!file) {
    image.value = initiallySet ? null : undefined
    return
  }

  const reference = references.value[file.id]

  if (!reference) {
    image.value = file
    return
  }

  const withReference: UploadFileInfoWithReference = { ...file, reference }
  image.value = withReference
}

// Uploads immediately on pick, mirroring PostFiles.vue's customRequest, so the form's
// own submit only ever sends the resulting identifier - never the raw file. naive-ui
// generates its own local preview from the raw File automatically (list-type
// "image-card"), so nothing extra is needed for that here.
function customRequest({ file, onFinish, onError, onProgress }: UploadCustomRequestOptions) {
  if (!file.file) {
    onError()
    return
  }

  uploading.value = true
  chunkedUpload
    .upload(file.file, { onProgress: (percent) => onProgress({ percent }) })
    .then((data) => {
      references.value[file.id] = data.uuid
      onFinish()
    })
    .catch(() => onError())
    .finally(() => {
      uploading.value = false
    })
}
</script>

<style scoped></style>
