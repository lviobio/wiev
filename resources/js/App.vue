<template>
  <AppProviderStack :stack="providerStack">
    <MultiRouterContext type="main" name="main">
      <AppSuspense>
        <MultiRouterContextActivator>
          <RouterView />
        </MultiRouterContextActivator>
      </AppSuspense>
    </MultiRouterContext>
    <MultiRouterContext type="main" name="main-2">
      <AppSuspense>
        <MultiRouterContextActivator>
          <RouterView />
        </MultiRouterContextActivator>
      </AppSuspense>
    </MultiRouterContext>
    <MultiRouterContext type="main" name="main-3">
      <AppSuspense>
        <MultiRouterContextActivator>
          <RouterView />
        </MultiRouterContextActivator>
      </AppSuspense>
    </MultiRouterContext>
    <!--      <DesktopWindowsHost />-->
  </AppProviderStack>
</template>

<script setup lang="ts">
import AppAuthProvider from '@/components/AppAuthProvider.vue'
import { defineProviderStack } from '@/components/AppProviderStack.vue'
import MultiRouterContext from '@/core/multi-router/components/MultiRouterContext.vue'
import MultiRouterContextActivator from '@/core/multi-router/components/MultiRouterContextActivator.vue'
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
