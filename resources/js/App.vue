<template>
  <AppProviderStack :stack="providerStack">
    <ContextStorageCollection>
      <AppSuspense>
        <ContextStorageProvider item-key="main">
          <ContextStorageActivator>
            <RouterViewCustom />
          </ContextStorageActivator>
        </ContextStorageProvider>
      </AppSuspense>
      <AppSuspense>
        <DesktopWindowsHost />
      </AppSuspense>
    </ContextStorageCollection>
  </AppProviderStack>
</template>

<script setup lang="ts">
import AppAuthProvider from '@/components/AppAuthProvider.vue'
import { defineProviderStack } from '@/components/AppProviderStack.vue'
import DesktopWindowsHost from '@/core/navigator/windows/DesktopWindowsHost.vue'
import {
  GlobalTheme,
  lightTheme,
  NConfigProvider,
  NMessageProvider,
  NNotificationProvider,
} from 'naive-ui'
import { ref } from 'vue'
import {
  ContextStorageActivator,
  ContextStorageCollection,
  ContextStorageProvider,
} from 'vue-context-storage'

const theme = ref<GlobalTheme | null>(lightTheme)

const providerStack = defineProviderStack([
  { component: NConfigProvider, props: { preflightStyleDisabled: true, theme: theme } },
  NMessageProvider,
  NNotificationProvider,
  AppAuthProvider,
])
</script>
