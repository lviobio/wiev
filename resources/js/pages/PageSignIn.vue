<template>
  <div class="space-y-4 p-6 sm:p-8 md:space-y-6">
    <h1 class="text-xl leading-tight font-bold tracking-tight text-gray-900 md:text-2xl">
      Sign in to account
    </h1>
    <NForm ref="formRef" :model="form" :rules="rules" @submit.prevent="handleSubmit">
      <NFormItem label="Enter email" path="email">
        <NInput
          id="email"
          v-model:value="form.email"
          :input-props="{
            type: 'email',
            autocomplete: 'email',
          }"
          placeholder="Email"
        />
      </NFormItem>
      <NFormItem label="Enter password" path="password">
        <NInput
          id="password"
          v-model:value="form.password"
          :input-props="{
            autocomplete: 'current-password',
          }"
          type="password"
          placeholder="Password"
        />
      </NFormItem>
      <NButton type="primary" attr-type="submit" class="mt-6"> Sign in </NButton>
      <p class="mt-6 text-sm font-light text-gray-500">
        Don't have an account?
        <RouterLink :to="{ name: 'sign-up' }" class="text-primary-600 font-medium hover:underline">
          Sign up
        </RouterLink>
      </p>
    </NForm>
  </div>
</template>

<script setup lang="ts">
import { useUserStore } from '@/composables/useUserStore'
import { FormRules, useMessage } from 'naive-ui'
import { reactive, ref } from 'vue'

interface FormInterface {
  email: string | null
  password: string | null
}

const userStore = useUserStore()
const message = useMessage()

const formRef = ref()

const form = reactive<FormInterface>({
  email: null,
  password: null,
})

const rules: FormRules = {
  email: [
    { required: true, message: 'Enter email' },
    { type: 'email', message: 'Enter valid email' },
  ],
  password: { required: true, message: 'Enter password', trigger: 'blur' },
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()

    await userStore.authorize({
      email: form.email,
      password: form.password,
    })
  } catch {
    message.error('Please fix errors in the form')
  }
}
</script>
