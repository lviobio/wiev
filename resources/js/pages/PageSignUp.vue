<template>
  <div class="space-y-4 p-6 sm:p-8 md:space-y-6">
    <h1 class="text-xl leading-tight font-bold tracking-tight text-gray-900 md:text-2xl">
      Registration
    </h1>
    <NForm ref="formRef" :model="form" :rules="rules" @submit.prevent="handleSubmit">
      <NFormItem :show-feedback="true" label="Enter your name" path="first_name">
        <NInput
          v-model:value="form.first_name"
          :input-props="{
            autocomplete: 'given-name',
          }"
          placeholder="Name"
        />
      </NFormItem>
      <NFormItem :show-feedback="true" label="Enter your surname" path="last_name">
        <NInput
          v-model:value="form.last_name"
          :input-props="{
            autocomplete: 'family-name',
          }"
          placeholder="Surname"
        />
      </NFormItem>
      <NFormItem :show-feedback="true" label="Enter email" path="email">
        <NInput
          v-model:value="form.email"
          :input-props="{
            type: 'email',
            autocomplete: 'email',
          }"
          placeholder="Email"
        />
      </NFormItem>
      <NFormItem :show-feedback="true" label="Enter password" path="password">
        <NInput
          v-model:value="form.password"
          :input-props="{
            autocomplete: 'new-password',
          }"
          type="password"
          placeholder="Password"
        />
      </NFormItem>
      <NFormItem :show-feedback="true" label="Confirm password" path="password_confirmation">
        <NInput
          v-model:value="form.password_confirmation"
          :input-props="{
            autocomplete: 'new-password',
          }"
          type="password"
          placeholder="Confirm password"
        />
      </NFormItem>
      <NFormItem :show-label="false" path="agreement">
        <NCheckbox v-model:checked="form.agreement"> I agree to the privacy policy </NCheckbox>
      </NFormItem>
      <NButton type="primary" attr-type="submit" class="mt-6"> Register </NButton>
      <p class="mt-6 text-sm font-light text-gray-500">
        Already have an account?
        <RouterLink :to="{ name: 'sign-in' }" class="text-primary-600 font-medium hover:underline">
          Sign in to the account
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
  first_name: string | null
  last_name: string | null
  email: string | null
  password: string | null
  password_confirmation: string | null
  agreement: boolean
}

const userStore = useUserStore()
const message = useMessage()

const formRef = ref()

const form = reactive<FormInterface>({
  first_name: null,
  last_name: null,
  email: null,
  password: null,
  password_confirmation: null,
  agreement: false,
})

const rules: FormRules = {
  first_name: {
    required: true,
    message: 'Enter your name',
  },
  last_name: {
    required: true,
    message: 'Enter your surname',
    trigger: 'blur',
  },
  email: [
    {
      required: true,
      message: 'Enter email',
    },
    {
      type: 'email',
      message: 'Enter valid email',
    },
  ],
  password: [
    {
      required: true,
      message: 'Enter password',
    },
    {
      min: 8,
      message: 'Password must be at least 8 characters',
    },
  ],
  password_confirmation: [
    {
      required: true,
      message: 'Repeat password',
    },
    {
      min: 8,
      message: 'Password must be at least 8 characters',
    },
  ],
  agreement: {
    required: true,
    validator: (_, value: boolean): boolean => value,
    message: 'You must agree to the privacy policy',
  },
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()

    await userStore.register({
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      agreement: form.agreement,
    })
  } catch {
    message.error('Please correct errors in the form')
  }
}
</script>
