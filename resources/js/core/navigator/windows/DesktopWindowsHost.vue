<script setup lang="ts">
import DesktopWindow from '@/components/DesktopWindow.vue'
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import WindowContext from '@/core/navigator/windows/WindowContext.vue'
import WindowRouterContext from '@/core/navigator/windows/WindowRouterContext.vue'
import { createContext } from '@/core/utils/context'
import { useDesktopWindows } from './useDesktopWindows'

const mgr = useDesktopWindows()

// const {Provider: WindowKeyProvider} = createContext(DesktopWindowKeySymbol)
</script>

<template>
  <div class="windows-host pointer-events-none fixed inset-0 z-[3]">
    <template v-for="w in mgr.windows" :key="w.windowId">
      <component :is="createContext(DesktopWindowKeySymbol, () => w.windowId).Provider">
        <DesktopContext :context-key="`window-${w.windowId}`">
          <AppCustomRouter>
            <WindowRouterContext>
              <DesktopContextInitializer>
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
                  <WindowContext>
                    <RouterView v-if="w.route" />
                    <!--                      <component v-else-if="w.component" :is="w.component" v-bind="w.props" />-->
                    <!--                      <p v-else>No component</p>-->
                  </WindowContext>
                </DesktopWindow>
              </DesktopContextInitializer>
            </WindowRouterContext>
          </AppCustomRouter>
        </DesktopContext>
      </component>
    </template>
  </div>
</template>
