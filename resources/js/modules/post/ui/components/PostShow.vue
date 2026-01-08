<script setup lang="tsx">
import Icon from '@/modules/post/icon'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { postRouteNames } from '@/modules/post/router/names'
import { PostIdentifier } from '@/modules/post/types'

const { repository, id } = defineProps<{
  id: PostIdentifier
  repository: PostRepository
}>()

const { data } = await repository.find(id)

const router = useRouter()

function onEdit() {
  router.push({
    name: postRouteNames.edit,
    params: { id: data.id },
    title: `Edit Post #${data.id}`,
  })
}

function onBack() {
  setTimeout(() => {
    router.push({
      name: postRouteNames.index,
      title: `Posts`,
    })
  }, 1000)
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
    <template #action>
      <NFlex>
        <NButton size="small" @click="onEdit">Edit</NButton>
        <NButton size="small" @click="onBack">Back delayed+1s</NButton>
      </NFlex>
    </template>
  </NThing>
</template>

<style scoped></style>
