<template>
  <AppProviderStack :stack="providerStack">
    <AppSuspense>
      <DesktopContext context-key="main">
        <DesktopContextInitializer>
          <DesktopContextActivator>
            <RouterView />
          </DesktopContextActivator>
        </DesktopContextInitializer>
      </DesktopContext>
    </AppSuspense>
    <AppSuspense>
      <!--      <DesktopWindowsHost />-->
    </AppSuspense>
  </AppProviderStack>
</template>

<script setup lang="ts">
import AppAuthProvider from '@/components/AppAuthProvider.vue'
import { defineProviderStack } from '@/components/AppProviderStack.vue'
import DesktopContextManager from '@/components/DesktopContextManager.vue'
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
  DesktopContextManager,
])
</script>
