<script setup lang="tsx">
import AppForm from '@/components/AppForm'
import { useNaiveForm } from '@/composables/useNaiveForm'
import { createEmptyObjectFromSchema, prepareFormData } from '@/core/utils/form-schemas'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import Form from '@/modules/post/ui/components/PostCreate/PostCreateForm.vue'
import {
  PostCreateFormData,
  postCreateFormSchema,
} from '@/modules/post/ui/components/PostCreate/index'

const message = useMessage()

const emit = defineEmits<{
  created: [Awaited<ReturnType<typeof repository.create>>]
}>()

const { repository } = defineProps<{
  repository: PostRepository
}>()

const { formRef, formModel, formValidate, formLoading } = useNaiveForm<PostCreateFormData>(
  createEmptyObjectFromSchema(postCreateFormSchema),
)

const handleSubmit = () =>
  formValidate(() =>
    repository
      .create({
        data: prepareFormData(formModel.value),
      })
      .then(
        (result) => {
          message.success('Post created successfully')
          emit('created', result)
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
