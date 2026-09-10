<script setup lang="tsx">
import { PostFileRepository } from '@/modules/post/repositories/PostFileRepository'
import { PostFile, PostIdentifier } from '@/modules/post/types'
import type { UploadCustomRequestOptions } from 'naive-ui'

const { id, repository } = defineProps<{
  id: PostIdentifier
  repository: PostFileRepository
}>()

const message = useMessage()
const dialog = useDialog()

const files = ref<PostFile[]>([])
const loading = ref(false)
const uploading = ref(false)
const renamingId = ref<string | null>(null)
const renameValue = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await repository.list(id)
    files.value = data
  } finally {
    loading.value = false
  }
}

await load()

function customRequest({ file, onFinish, onError }: UploadCustomRequestOptions) {
  if (!file.file) {
    onError()
    return
  }

  uploading.value = true
  repository
    .attach(id, { data: { file: file.file } })
    .then(({ data }) => {
      files.value.push(data)
      message.success('File attached')
      onFinish()
    })
    .catch(() => onError())
    .finally(() => {
      uploading.value = false
    })
}

function startRename(file: PostFile) {
  renamingId.value = file.uuid
  renameValue.value = file.name
}

function cancelRename() {
  renamingId.value = null
}

async function confirmRename(file: PostFile) {
  if (!renameValue.value.trim()) {
    return
  }

  const { data } = await repository.rename(id, file.uuid, { data: { name: renameValue.value } })

  const index = files.value.findIndex((item) => item.uuid === file.uuid)
  if (index !== -1) {
    files.value[index] = data
  }

  renamingId.value = null
  message.success('File renamed')
}

function confirmDetach(file: PostFile) {
  dialog.warning({
    title: 'Delete file',
    content: `Delete "${file.name}"?`,
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      await repository.detach(id, file.uuid)
      files.value = files.value.filter((item) => item.uuid !== file.uuid)
      message.success('File deleted')
    },
  })
}

function download(file: PostFile) {
  repository.download(id, file).catch(() => {
    message.error('Failed to download file')
  })
}
</script>

<template>
  <NFlex vertical>
    <NUpload
      :custom-request="customRequest"
      :show-file-list="false"
      :multiple="false"
      :disabled="uploading"
    >
      <NButton :loading="uploading">Attach file</NButton>
    </NUpload>

    <NList v-if="loading || files.length" bordered :show-divider="true">
      <NListItem v-for="file in files" :key="file.uuid">
        <NThing>
          <template #header>
            <NInput
              v-if="renamingId === file.uuid"
              v-model:value="renameValue"
              size="small"
              @keyup.enter="confirmRename(file)"
              @keyup.escape="cancelRename"
            />
            <span v-else>{{ file.name }}</span>
          </template>
          <template #description>
            {{ file.file_name }}
          </template>
          <template #footer>
            <NFlex :size="4">
              <template v-if="renamingId === file.uuid">
                <NButton size="tiny" type="primary" @click="confirmRename(file)">Save</NButton>
                <NButton size="tiny" @click="cancelRename">Cancel</NButton>
              </template>
              <template v-else>
                <NButton size="tiny" @click="download(file)">Download</NButton>
                <NButton size="tiny" @click="startRename(file)">Rename</NButton>
                <NButton size="tiny" type="error" @click="confirmDetach(file)">Delete</NButton>
              </template>
            </NFlex>
          </template>
        </NThing>
      </NListItem>
    </NList>
    <NEmpty v-else description="No files attached" />
  </NFlex>
</template>

<style scoped></style>
