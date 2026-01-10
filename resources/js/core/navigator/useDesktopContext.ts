import { desktopContextKey } from '@/core/injectionSymbols'
import { inject } from 'vue'

export function useDesktopContext() {
  const contextKey = inject(desktopContextKey)!

  return {
    contextKey,
  }
}
