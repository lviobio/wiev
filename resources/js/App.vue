<template>
  <AppProviderStack :stack="providerStack">
    <ContextStorageCollection :handlers="[ContextStorageQueryHandler]">
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
import ContextStorageActivator from '@/core/context-storage/components/ContextStorageActivator.vue'
import ContextStorageCollection from '@/core/context-storage/components/ContextStorageCollection.vue'
import ContextStorageProvider from '@/core/context-storage/components/ContextStorageProvider.vue'
import { ContextStorageQueryHandler } from '@/core/context-storage/handlers/query'
import DesktopWindowsHost from '@/core/navigator/windows/DesktopWindowsHost.vue'
import {
  GlobalTheme,
  lightTheme,
  NConfigProvider,
  NMessageProvider,
  NNotificationProvider,
} from 'naive-ui'
import { ref } from 'vue'

const theme = ref<GlobalTheme | null>(lightTheme)

const providerStack = defineProviderStack([
  { component: NConfigProvider, props: { preflightStyleDisabled: true, theme: theme } },
  NMessageProvider,
  NNotificationProvider,
  AppAuthProvider,
])
</script>
