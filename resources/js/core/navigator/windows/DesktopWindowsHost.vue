<script setup lang="ts">
import DesktopWindow from '@/components/DesktopWindow.vue'
import WindowContext from '@/core/navigator/windows/WindowContext.vue'
import { useDesktopWindows } from './useDesktopWindows'

const mgr = useDesktopWindows()
</script>

<template>
  <div class="windows-host pointer-events-none fixed inset-0 z-[3]">
    <template v-for="w in mgr.windows" :key="w.windowId">
      <DesktopWindow
        :title="w.title"
        :x="w.x"
        :y="w.y"
        :z="w.z"
        :width="w.width"
        @focus="mgr.bringToFront(w.windowId)"
        @update:x="(v) => mgr.updateX(w.windowId, v)"
        @update:y="(v) => mgr.updateY(w.windowId, v)"
        @close="() => mgr.close(w.windowId)"
      >
        <WindowContext :key-val="w.windowId">
          <component :is="w.component" v-bind="w.props" />
        </WindowContext>
      </DesktopWindow>
    </template>
  </div>
</template>
