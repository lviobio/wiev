<template>
  <AppProviderStack :stack="providerStack">
    <ContextStorage>
      <AppSuspense>
        <RouterView />
      </AppSuspense>
      <!--      <AppSuspense>-->
      <!--        <DesktopWindowsHost />-->
      <!--      </AppSuspense>-->
    </ContextStorage>
  </AppProviderStack>
</template>

<script setup lang="ts">
import AppAuthProvider from '@/components/AppAuthProvider.vue'
import { defineProviderStack } from '@/components/AppProviderStack.vue'
import {
  GlobalTheme,
  lightTheme,
  NConfigProvider,
  NDialogProvider,
  NMessageProvider,
  NNotificationProvider,
} from 'naive-ui'
import { ref } from 'vue'
import { ContextStorage } from 'vue-context-storage'

const theme = ref<GlobalTheme | null>(lightTheme)

const providerStack = defineProviderStack([
  { component: NConfigProvider, props: { preflightStyleDisabled: true, theme: theme } },
  NMessageProvider,
  NDialogProvider,
  NNotificationProvider,
  AppAuthProvider,
])
</script>
