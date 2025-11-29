<template>
  <div>Logging out...</div>
</template>

<script setup lang="ts">
import { useAppNavigator } from '@/core/navigator/useAppNavigator'
import { PageExpose } from '@/core/types'
import { loginRouteGenerator } from '@/modules/app/authentication/router/names'
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

const appNavigator = useAppNavigator()

onMounted(() => {
  authStore.logout()

  appNavigator.navigate(loginRouteGenerator.index())
})
</script>
