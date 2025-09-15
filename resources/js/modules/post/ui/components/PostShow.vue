<script setup lang="tsx">
import Icon from '@/modules/post/icon'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { PostIdentifier } from '@/modules/post/types'

const { repository, id } = defineProps<{
  id: PostIdentifier
  repository: PostRepository
}>()

const { data } = await repository.find(id)
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
  </NThing>
</template>

<style scoped></style>
