<script setup lang="ts">
import WindowContext from '@/core/navigator/windows/WindowContext.vue'
import WindowRouterContext from '@/core/navigator/windows/WindowRouterContext.vue'
import { createContext } from '@/core/utils/context'
import { viewDepthKey } from 'vue-router'

defineProps<{
  windowId: number
}>()
</script>

<template>
  <DesktopContext :context-key="`window-${windowId}`">
    <AppCustomRouter>
      <WindowRouterContext>
        <DesktopContextInitializer>
          <WindowContext>
            <component :is="createContext(viewDepthKey, () => 2).Provider">
              <RouterView />
            </component>
          </WindowContext>
        </DesktopContextInitializer>
      </WindowRouterContext>
    </AppCustomRouter>
  </DesktopContext>
</template>
