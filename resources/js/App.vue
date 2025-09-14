<template>
  <NConfigProvider preflight-style-disabled :theme="theme">
    <NMessageProvider>
      <NNotificationProvider>
        <RouterView />
      </NNotificationProvider>
    </NMessageProvider>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { GlobalTheme, lightTheme } from 'naive-ui'
import { ref } from 'vue'

const theme = ref<GlobalTheme | null>(lightTheme)
</script>
