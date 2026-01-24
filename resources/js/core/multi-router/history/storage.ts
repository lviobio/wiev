import type { VirtualStack } from './types'
import { 
  type ContextStorageAdapter, 
  type MaybePromise,
  type StoredVirtualStack,
  SessionStorageAdapter,
  resolveValue,
} from './storage/index'

export type { ContextStorageAdapter, MaybePromise, StoredVirtualStack }

export class VirtualStackStorage {
  private adapter: ContextStorageAdapter

  constructor(adapter?: ContextStorageAdapter) {
    this.adapter = adapter ?? new SessionStorageAdapter()
  }

  save(contextKey: string, stack: VirtualStack): MaybePromise<void> {
    return this.adapter.saveStack(contextKey, stack)
  }

  restore(contextKey: string): MaybePromise<VirtualStack | null> {
    return this.adapter.restoreStack(contextKey)
  }

  clear(contextKey: string): MaybePromise<void> {
    return this.adapter.clearStack(contextKey)
  }

  saveActiveContext(contextKey: string): MaybePromise<void> {
    return this.adapter.saveActiveContext(contextKey)
  }

  getActiveContext(): MaybePromise<string | null> {
    return this.adapter.getActiveContext()
  }

  saveActiveHistoryContext(contextKey: string): MaybePromise<void> {
    return this.adapter.saveActiveHistoryContext(contextKey)
  }

  getActiveHistoryContext(): MaybePromise<string | null> {
    return this.adapter.getActiveHistoryContext()
  }

  resolveValue = resolveValue
}
