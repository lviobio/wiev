<script lang="ts">
import { z } from 'zod'
import { toValidNumber } from 'zod-valid'

export const propsSchema = z.object({
  id: toValidNumber({ allow: 'none', preserve: false }),
})

export type PropsSchema = z.infer<typeof propsSchema>
</script>

<script setup lang="ts">
import { useAppNavigator } from '@/core/navigator/useAppNavigator'
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { Edit, Show } from '@/modules/post/ui'

const repository = usePostRepository()

const _ = defineProps<{ params: PropsSchema }>()
const props = propsSchema.parse(_.params)

const appNavigator = useAppNavigator()

const onUpdated = () =>
  appNavigator.openOrNavigate({
    component: Show.Page,
    params: { id: props.id },
    title: `Post #${props.id}`,
  })
</script>

<template>
  <NFlex>
    <Edit.Component :repository="repository" :id="props.id" @updated="onUpdated" />
  </NFlex>
</template>

<style scoped></style>
