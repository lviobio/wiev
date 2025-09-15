<script setup lang="tsx">
import AppForm from '@/components/AppForm'
import { useNaiveForm } from '@/composables/useNaiveForm'
import { PostRepository } from '@/modules/post/repositories/PostRepository'
import { Create } from '@/modules/post/ui'
import { PostCreateFormData } from '@/modules/post/ui/components/PostCreate/PostCreateForm.vue'

const message = useMessage()

const emit = defineEmits<{
  created: [Awaited<ReturnType<typeof repository.create>>]
}>()

const { repository } = defineProps<{
  repository: PostRepository
}>()

const { formRef, formModel, formValidate, formLoading } = useNaiveForm<PostCreateFormData>({
  title: '',
  content: '',
})

const handleSubmit = () =>
  formValidate(() =>
    repository
      .create({
        data: formModel.value,
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
      <Create.ComponentForm v-model="formModel" />

      <NButton @click="handleSubmit" :loading="formLoading">Submit</NButton>
    </AppForm>
  </NFlex>
</template>

<style scoped></style>
