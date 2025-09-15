<script setup lang="tsx">
import AppForm from '@/components/AppForm'
import { useNaiveForm } from '@/composables/useNaiveForm'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { PostIdentifier } from '@/modules/post/types'
import { Create } from '@/modules/post/ui'
import { PostEditFormData } from './PostEditForm.vue'

const message = useMessage()

const emit = defineEmits<{
  updated: [Awaited<ReturnType<typeof repository.update>>]
}>()

const { repository, id } = defineProps<{
  id: PostIdentifier
  repository: PostRepository
}>()

const { data } = await repository.find(id)

const { formRef, formModel, formValidate, formLoading } = useNaiveForm<PostEditFormData>({
  title: data.title,
  content: data.content ?? '',
})

const handleSubmit = () =>
  formValidate(() =>
    repository
      .update(id, {
        data: formModel.value,
      })
      .then(
        (result) => {
          message.success('Post updated successfully')
          emit('updated', result)
        },
        (err) => {
          console.log(err)
          throw err
        },
      ),
  )
</script>

<template>
  <NFlex vertical>
    <AppForm ref="formRef" :model="formModel">
      <Create.ComponentForm v-model="formModel" />

      <NButton @click="handleSubmit" :loading="formLoading">Submit</NButton>
    </AppForm>
  </NFlex>
</template>

<style scoped></style>
