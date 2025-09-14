<template>
  <n-config-provider preflight-style-disabled :theme="theme">
    <n-message-provider>
      <n-notification-provider>
        <RouterView />
      </n-notification-provider>
    </n-message-provider>
  </n-config-provider>
</template>

<script setup lang="ts">
import { GlobalTheme, lightTheme } from 'naive-ui'
import { ref } from 'vue'

const theme = ref<GlobalTheme | null>(lightTheme)
</script>
