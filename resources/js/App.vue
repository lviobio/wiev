<template>
  <AppProviderStack :stack="providerStack">
    <MultiRouterContext type="main" name="root">
      <!--      <AppSuspense>-->
      <RouterView />
      <!--      </AppSuspense>-->
    </MultiRouterContext>
    <!--      <DesktopWindowsHost />-->
  </AppProviderStack>
</template>

<script setup lang="ts">
import AppAuthProvider from '@/components/AppAuthProvider.vue'
import { defineProviderStack } from '@/components/AppProviderStack.vue'
import MultiRouterContext from '@/core/multi-router/components/MultiRouterContext.vue'
import {
  darkTheme,
  GlobalTheme,
  NConfigProvider,
  NMessageProvider,
  NNotificationProvider,
} from 'naive-ui'
import { ref } from 'vue'
import { ContextStorageQueryHandler } from 'vue-context-storage'

// ContextStorageQueryHandler.configure({ preserveUnusedKeys: true })
ContextStorageQueryHandler.configure({ mode: 'push' })

const theme = ref<GlobalTheme | null>(darkTheme)

const providerStack = defineProviderStack([
  { component: NConfigProvider, props: { preflightStyleDisabled: true, theme: theme.value } },
  NMessageProvider,
  NNotificationProvider,
  AppAuthProvider,
])
</script>
