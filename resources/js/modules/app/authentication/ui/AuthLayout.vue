<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-16">
    <div v-if="!isLoggedIn">
      <RouterView v-slot="{ Component }">
        <component :is="Component" @success="goHome" />
      </RouterView>
    </div>
    <div v-else>You already logged in, redirecting...</div>
  </div>
</template>

<script setup lang="ts">
import { injectLoginData } from '@/core/auth'

const loginData = injectLoginData()

const isLoggedIn = !!loginData.value

const router = useRouter()

function goHome() {
  router.push({ name: 'home' })
}

onMounted(() => {
  if (isLoggedIn) {
    setTimeout(goHome, 1500)
  }
})
</script>
