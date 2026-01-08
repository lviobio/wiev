import { desktopContextKey, desktopContextManager } from '@/core/injectionSymbols'
import { inject } from 'vue'

export function useDesktopContext() {
  const manager = inject(desktopContextManager)!
  const contextKey = inject(desktopContextKey)!

  return {
    contextKey,
    initialize: () => manager.initialize(contextKey),
    activate: () => manager.setActive(contextKey),
  }
}
