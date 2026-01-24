import type { VirtualStack } from '../types'
import type { ContextStorageAdapter, StoredVirtualStack } from './types'

const STACK_STORAGE_PREFIX = '__multiRouter_stack_'
const ACTIVE_CONTEXT_STORAGE_KEY = '__multiRouterActiveContext'

export class SessionStorageAdapter implements ContextStorageAdapter {
  private getStackStorageKey(contextKey: string): string {
    return `${STACK_STORAGE_PREFIX}${contextKey}`
  }

  saveStack(contextKey: string, stack: VirtualStack): void {
    try {
      const data: StoredVirtualStack = {
        entries: stack.entries,
        position: stack.position,
      }
      sessionStorage.setItem(this.getStackStorageKey(contextKey), JSON.stringify(data))
    } catch (e) {
      console.warn('[SessionStorageAdapter] Failed to save stack:', e)
    }
  }

  restoreStack(contextKey: string): StoredVirtualStack | null {
    try {
      const stored = sessionStorage.getItem(this.getStackStorageKey(contextKey))
      if (!stored) return null

      const data = JSON.parse(stored) as StoredVirtualStack

      if (data && Array.isArray(data.entries) && data.entries.length > 0) {
        // Validate position is within bounds
        const position = Math.min(Math.max(0, data.position ?? 0), data.entries.length - 1)

        return {
          entries: data.entries,
          position,
        }
      }
    } catch (e) {
      console.warn('[SessionStorageAdapter] Failed to restore stack:', e)
    }
    return null
  }

  clearStack(contextKey: string): void {
    try {
      sessionStorage.removeItem(this.getStackStorageKey(contextKey))
    } catch (e) {
      console.warn('[SessionStorageAdapter] Failed to clear stack:', e)
    }
  }

  saveActiveContext(contextKey: string): void {
    try {
      sessionStorage.setItem(ACTIVE_CONTEXT_STORAGE_KEY, contextKey)
    } catch (e) {
      console.warn('[SessionStorageAdapter] Failed to save active context:', e)
    }
  }

  getActiveContext(): string | null {
    try {
      return sessionStorage.getItem(ACTIVE_CONTEXT_STORAGE_KEY)
    } catch (e) {
      return null
    }
  }
}
