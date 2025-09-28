<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { Show } from '@/modules/post/ui/components'
import { postShowPageSchema as propsSchema, PostShowPageSchema as PropsSchema } from './schemas'

const repository = usePostRepository()

const _ = defineProps<{ params: PropsSchema }>()
const props = propsSchema.parse(_.params)
</script>

<template>
  <NFlex>
    <Show.Component :repository="repository" :id="props.id" />
  </NFlex>
</template>

<style scoped></style>
