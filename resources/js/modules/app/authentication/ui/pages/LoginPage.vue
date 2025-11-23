<script setup lang="ts">
import { injectLoginData, LoginData } from '@/core/auth'
import {
  LoginResult,
  useAuthenticationRepository,
} from '@/modules/app/authentication/repositories/AuthenticationRepository'
import { Login } from '@/modules/app/authentication/ui/components'

const repository = useAuthenticationRepository()

const loginData = injectLoginData()

const emit = defineEmits<{ success: [] }>()

function handleSuccessLogin(result: LoginResult) {
  loginData.value = new LoginData(result.issued.credentials.token)
  emit('success')
}
</script>

<template>
  <div>
    <NCard>
      <Login.Form :repository="repository" @success="handleSuccessLogin" />
    </NCard>
  </div>
</template>

<style scoped></style>
