<script setup lang="tsx">
import { useAppNavigator } from '@/core/navigator/useAppNavigator'
import Icon from '@/modules/post/icon'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { postRouteNames } from '@/modules/post/router/names'
import { PostIdentifier } from '@/modules/post/types'

const { repository, id } = defineProps<{
  id: PostIdentifier
  repository: PostRepository
}>()

const { data } = await repository.find(id)

const appNavigator = useAppNavigator()

function onEdit() {
  appNavigator.navigate({
    name: postRouteNames.edit,
    params: { id: data.id },
    title: `Edit Post #${data.id}`,
  })
}

function onBack() {
  appNavigator.navigate({
    name: postRouteNames.index,
    title: `Posts`,
  })
}
</script>

<template>
  <NThing>
    <template #avatar>
      <NIcon :size="32">
        <Icon />
      </NIcon>
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
        <NButton size="small" @click="onBack">Back</NButton>
      </NFlex>
    </template>
  </NThing>
</template>

<style scoped></style>
