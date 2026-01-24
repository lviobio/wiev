import type { 
  ContextHistoryState, 
  HistoryLocation, 
  HistoryState, 
  NavigationCallback, 
  NavigationInformation, 
  VirtualStack 
} from './types'
import { 
  VirtualStackStorage, 
  type ContextStorageAdapter, 
  type MaybePromise 
} from './storage'

export class VirtualStackManager {
  private contexts = new Map<string, ContextHistoryState>()
  private storage: VirtualStackStorage

  constructor(storageAdapter?: ContextStorageAdapter) {
    this.storage = new VirtualStackStorage(storageAdapter)
  }

  has(contextKey: string): boolean {
    return this.contexts.has(contextKey)
  }

  get(contextKey: string): ContextHistoryState | undefined {
    return this.contexts.get(contextKey)
  }

  create(contextKey: string, initialStack: VirtualStack, historyEnabled: boolean = true): void {
    this.contexts.set(contextKey, {
      virtualStack: initialStack,
      listeners: new Set(),
      historyEnabled,
    })
  }

  isHistoryEnabled(contextKey: string): boolean {
    return this.contexts.get(contextKey)?.historyEnabled ?? true
  }

  remove(contextKey: string): void {
    this.contexts.delete(contextKey)
    this.storage.clear(contextKey)
  }

  clear(): void {
    this.contexts.clear()
  }

  getLocation(contextKey: string, fallback: HistoryLocation): HistoryLocation {
    const context = this.contexts.get(contextKey)
    if (!context) return fallback
    return context.virtualStack.entries[context.virtualStack.position]?.location ?? fallback
  }

  getState(contextKey: string): HistoryState {
    const context = this.contexts.get(contextKey)
    if (!context) return {}
    return context.virtualStack.entries[context.virtualStack.position]?.state ?? {}
  }

  push(contextKey: string, location: HistoryLocation, state: HistoryState): number {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[VirtualStackManager] Context "${contextKey}" not found`)
    }

    // Truncate forward history and add new entry
    context.virtualStack.entries = context.virtualStack.entries.slice(0, context.virtualStack.position + 1)
    context.virtualStack.entries.push({ location, state })
    context.virtualStack.position = context.virtualStack.entries.length - 1

    this.save(contextKey)
    return context.virtualStack.position
  }

  replace(contextKey: string, location: HistoryLocation, state: HistoryState): number {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[VirtualStackManager] Context "${contextKey}" not found`)
    }

    context.virtualStack.entries[context.virtualStack.position] = { location, state }

    this.save(contextKey)
    return context.virtualStack.position
  }

  navigate(contextKey: string, delta: number): { from: HistoryLocation; to: HistoryLocation; newPosition: number } | null {
    const context = this.contexts.get(contextKey)
    if (!context) return null

    const newPosition = context.virtualStack.position + delta

    if (newPosition < 0 || newPosition >= context.virtualStack.entries.length) {
      return null
    }

    const from = context.virtualStack.entries[context.virtualStack.position].location
    context.virtualStack.position = newPosition
    const to = context.virtualStack.entries[newPosition].location

    this.save(contextKey)
    return { from, to, newPosition }
  }

  setPosition(contextKey: string, position: number): void {
    const context = this.contexts.get(contextKey)
    if (!context) return

    if (position >= 0 && position < context.virtualStack.entries.length) {
      context.virtualStack.position = position
      this.save(contextKey)
    }
  }

  ensureEntriesUpTo(contextKey: string, position: number, fallbackLocation: HistoryLocation): void {
    const context = this.contexts.get(contextKey)
    if (!context) return

    while (context.virtualStack.entries.length <= position) {
      context.virtualStack.entries.push({ location: fallbackLocation, state: {} })
    }
  }

  addListener(contextKey: string, callback: NavigationCallback): () => void {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[VirtualStackManager] Context "${contextKey}" not found`)
    }

    context.listeners.add(callback)
    return () => context.listeners.delete(callback)
  }

  notifyListeners(contextKey: string, to: HistoryLocation, from: HistoryLocation, info: NavigationInformation): void {
    const context = this.contexts.get(contextKey)
    if (!context) return

    context.listeners.forEach((callback) => callback(to, from, info))
  }

  // Storage methods - return MaybePromise for async adapter support
  save(contextKey: string): MaybePromise<void> {
    const context = this.contexts.get(contextKey)
    if (context) {
      return this.storage.save(contextKey, context.virtualStack)
    }
  }

  restore(contextKey: string): MaybePromise<VirtualStack | null> {
    return this.storage.restore(contextKey)
  }

  saveActiveContext(contextKey: string): MaybePromise<void> {
    return this.storage.saveActiveContext(contextKey)
  }

  getStoredActiveContext(): MaybePromise<string | null> {
    return this.storage.getActiveContext()
  }

  // Iteration
  entries(): IterableIterator<[string, ContextHistoryState]> {
    return this.contexts.entries()
  }
}
