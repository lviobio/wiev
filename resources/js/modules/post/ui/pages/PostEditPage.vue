<script setup lang="ts">
import { usePostRepository } from '@/modules/post/repositories/PostRepository'
import { postRouteGenerator } from '@/modules/post/routes'
import { Edit } from '@/modules/post/ui'
import { z } from 'zod'
import { toValidNumber } from 'zod-valid'

const repository = usePostRepository()

const propsSchema = z.object({
  id: toValidNumber({ allow: 'none', preserve: false }),
})

const _ = defineProps<{ params: unknown }>()
const props = propsSchema.parse(_.params)
</script>

<template>
  <NFlex>
    <Edit.Component
      :repository="repository"
      :id="props.id"
      @updated="$router.push(postRouteGenerator.show(props.id))"
    />
  </NFlex>
</template>

<style scoped></style>
