<template>
  <NConfigProvider preflight-style-disabled :theme="theme">
    <NMessageProvider>
      <NNotificationProvider>
        <AppSuspense>
          <RouterView />
        </AppSuspense>
        <AppSuspense>
          <DesktopWindowsHost />
        </AppSuspense>
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
