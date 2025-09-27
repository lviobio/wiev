<script setup lang="ts">
import DesktopWindow from '@/components/DesktopWindow.vue'
import WindowContext from '@/core/windows/WindowContext.vue'
import { useDesktopWindows } from './useDesktopWindows'

const mgr = useDesktopWindows()
</script>

<template>
  <div class="desktop-windows-host z-[3]">
    <template v-for="w in mgr.windows" :key="w.key">
      <DesktopWindow
        :title="w.title"
        :x="w.x"
        :y="w.y"
        :z="w.z"
        :width="w.width"
        @focus="mgr.bringToFront(w.key)"
        @update:x="(v) => mgr.updateX(w.key, v)"
        @update:y="(v) => mgr.updateY(w.key, v)"
        @close="() => mgr.close(w.key)"
      >
        <WindowContext :key-val="w.key">
          <component :is="w.component" v-bind="w.props" />
        </WindowContext>
      </DesktopWindow>
    </template>
  </div>
</template>

<style scoped>
.desktop-windows-host {
  position: fixed;
  inset: 0;
  pointer-events: none;
}
.desktop-windows-host :deep(.desktop-window) {
  pointer-events: auto;
}
</style>
