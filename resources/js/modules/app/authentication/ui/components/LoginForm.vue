<script setup lang="tsx">
import AppForm from '@/components/AppForm'
import { useNaiveForm } from '@/composables/useNaiveForm'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import {
  AuthenticationRepository,
  LoginForm,
  loginFormSchema,
  LoginResult,
} from '@/modules/app/authentication/repositories/AuthenticationRepository'

const message = useMessage()

const emit = defineEmits<{
  success: [LoginResult]
}>()

const { repository } = defineProps<{ repository: AuthenticationRepository }>()

const { formRef, formModel, formValidate, formLoading } = useNaiveForm<LoginForm>(
  createEmptyObjectFromSchema(loginFormSchema),
)

formModel.value.email = 'test@example.com'
formModel.value.password = 'password'

const handleSubmit = () =>
  formValidate(() =>
    repository
      .login({
        data: formModel.value,
      })
      .then(
        (result) => {
          message.success('Logged in successfully')
          emit('success', result.data)
        },
        (err) => {
          console.log(err)
          throw err
        },
      ),
  )
</script>

<template>
  <AppForm ref="formRef">
    <NFormItem label="E-Mail" path="email">
      <NInput v-model:value="formModel.email" />
    </NFormItem>

    <NFormItem label="Password" path="password">
      <NInput v-model:value="formModel.password" />
    </NFormItem>

    <NButton @click="handleSubmit" :loading="formLoading">Login</NButton>
  </AppForm>
</template>

<style scoped></style>
