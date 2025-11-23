<template>
  <NConfigProvider preflight-style-disabled :theme="theme">
    <NMessageProvider>
      <NNotificationProvider>
        <AppAuthProvider>
          <AppSuspense>
            <RouterView />
          </AppSuspense>
          <AppSuspense>
            <DesktopWindowsHost />
          </AppSuspense>
        </AppAuthProvider>
      </NNotificationProvider>
    </NMessageProvider>
  </NConfigProvider>
</template>

<script setup lang="ts">
import DesktopWindowsHost from '@/core/navigator/windows/DesktopWindowsHost.vue'
import { GlobalTheme, lightTheme } from 'naive-ui'
import { ref } from 'vue'

const theme = ref<GlobalTheme | null>(lightTheme)
</script>
