<script setup lang="tsx">
import AppForm from '@/components/AppForm'
import { useNaiveForm } from '@/composables/useNaiveForm'
import { prepareFormData } from '@/core/utils/form-schemas'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { PostIdentifier } from '@/modules/post/types'
import { PostEditFormData, postEditFormSchema } from '@/modules/post/ui/components/PostEdit/index'
import Form from './PostEditForm.vue'

const message = useMessage()

const emit = defineEmits<{
  updated: [Awaited<ReturnType<typeof repository.update>>]
}>()

const { repository, id } = defineProps<{
  id: PostIdentifier
  repository: PostRepository
}>()

const { data } = await repository.find(id)

const { formRef, formModel, formValidate, formLoading } = useNaiveForm<PostEditFormData>(
  postEditFormSchema.parse(data),
)

const handleSubmit = () =>
  formValidate(() =>
    repository
      .update(id, {
        data: prepareFormData(formModel.value),
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
      <Form v-model="formModel" />

      <NButton @click="handleSubmit" :loading="formLoading">Submit</NButton>
    </AppForm>
  </NFlex>
</template>

<style scoped></style>
