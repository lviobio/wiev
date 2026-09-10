<script setup lang="tsx">
import Icon from '@/modules/post/icon'
import { usePostFileRepository } from '@/modules/post/repositories/PostFileRepository'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { postRouteNames } from '@/modules/post/router/names'
import { PostIdentifier } from '@/modules/post/types'
import Files from '@/modules/post/ui/components/PostFiles'

const { repository, id } = defineProps<{
  id: PostIdentifier
  repository: PostRepository
}>()

const { data } = await repository.find(id)

const fileRepository = usePostFileRepository()

const router = useRouter()

function onEdit() {
  router.push({
    name: postRouteNames.edit,
    params: { id: data.id },
    title: `Edit Post #${data.id}`,
  })
}

function onBack() {
  router.push({
    name: postRouteNames.index,
    title: `Posts`,
  })
}
</script>

<template>
  <NThing>
    <template #avatar>
      <NIcon :size="32" v-if="!data.cover">
        <Icon />
      </NIcon>
      <NImage :width="32" v-else :src="data.cover" />
    </template>
    <template #header>
      <span>{{ data.title }}</span>
    </template>
    <template #description>
      <span>ID: {{ data.id }}</span>
    </template>
    <div>{{ data.content }}</div>
    <template #footer>
      <Files.Component :id="data.id" :repository="fileRepository" />
    </template>
    <template #action>
      <NFlex>
        <NButton size="small" @click="onEdit">Edit</NButton>
        <NButton size="small" @click="onBack">Back</NButton>
      </NFlex>
    </template>
  </NThing>
</template>

<style scoped></style>
