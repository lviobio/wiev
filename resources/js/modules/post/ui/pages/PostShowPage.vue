<script lang="ts">
import { z } from 'zod'
import { toValidNumber } from 'zod-valid'

const propsSchema = z.object({
  id: toValidNumber({ allow: 'none', preserve: false }),
})

export type PropsSchema = z.infer<typeof propsSchema>

export { propsSchema }
</script>

<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { Show } from '@/modules/post/ui'

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
