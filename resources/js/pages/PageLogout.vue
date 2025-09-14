<template>
  <div>Logging out...</div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/composables/useAuthStore'
import { PageExpose } from '@/core/types'
import { onMounted } from 'vue'

defineExpose<PageExpose>({
  title: 'Logging out',
  breadcrumbs: [
    {
      title: 'Logging out',
      route: { name: 'logout' },
    },
  ],
})

const authStore = useAuthStore()

onMounted(() => {
  authStore.logout()

  window.location.reload()
})
</script>
