<template>
  <div>Logging out...</div>
</template>

<script setup lang="ts">
import { logout } from '@/core/auth'
import { PageExpose } from '@/core/types'
import { loginRouteGenerator } from '@/modules/app/authentication/router/names'
import { onMounted } from 'vue'

const router = useRouter()

defineExpose<PageExpose>({
  title: 'Logging out',
  breadcrumbs: [
    {
      title: 'Logging out',
      route: { name: 'logout' },
    },
  ],
})

onMounted(() => {
  logout()

  router.push(loginRouteGenerator.index())
})
</script>
