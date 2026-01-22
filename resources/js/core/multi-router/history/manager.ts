import type { RouterHistory } from 'vue-router'
import { ContextHistoryProxy } from './context-proxy'
import {
  NavigationDirection,
  NavigationType,
  type ContextHistoryState,
  type HistoryBuilder,
  type HistoryLocation,
  type HistoryState,
  type NavigationCallback,
  type NavigationInformation,
  type VirtualStack,
} from './types'

export class MultiRouterHistoryManager {
  private baseHistory: RouterHistory
  private contexts = new Map<string, ContextHistoryState>()
  private activeHistoryContextKey: string | null = null
  private historyContextStack: string[] = []
  private baseHistoryCleanup: (() => void) | null = null

  constructor(historyBuilder: HistoryBuilder) {
    this.baseHistory = historyBuilder()
    this.baseHistoryCleanup = this.baseHistory.listen(this.handlePopState.bind(this))
  }

  get base() {
    return this.baseHistory.base
  }

  get location(): HistoryLocation {
    return this.baseHistory.location
  }

  get state(): HistoryState {
    return this.baseHistory.state
  }

  createContextHistory(contextKey: string, initialLocation?: string): RouterHistory {
    if (!this.contexts.has(contextKey)) {
      this.contexts.set(contextKey, {
        virtualStack: this.createInitialVirtualStack(initialLocation),
        listeners: new Set(),
      })
    }

    return new ContextHistoryProxy(contextKey, this)
  }

  removeContextHistory(contextKey: string): void {
    this.contexts.delete(contextKey)

    if (this.activeHistoryContextKey === contextKey) {
      this.fallbackToPreviousHistoryContext()
    }

    this.historyContextStack = this.historyContextStack.filter((k) => k !== contextKey)
  }

  setActiveHistoryContext(contextKey: string): void {
    if (!this.contexts.has(contextKey)) {
      throw new Error(`[MultiRouterHistory] Context "${contextKey}" not registered`)
    }

    if (this.activeHistoryContextKey === contextKey) {
      return
    }

    const previousKey = this.activeHistoryContextKey

    if (previousKey) {
      this.saveCurrentUrlToVirtualStack(previousKey)
      this.historyContextStack = this.historyContextStack.filter((k) => k !== previousKey)
      this.historyContextStack.push(previousKey)
    }

    this.activeHistoryContextKey = contextKey
    this.restoreUrlFromVirtualStack(contextKey)

    console.log('[MultiRouterHistory] setActiveHistoryContext', {
      from: previousKey,
      to: contextKey,
    })
  }

  clearActiveHistoryContext(contextKey: string): void {
    if (this.activeHistoryContextKey !== contextKey) {
      return
    }

    this.fallbackToPreviousHistoryContext()
  }

  getActiveHistoryContextKey(): string | null {
    return this.activeHistoryContextKey
  }

  private fallbackToPreviousHistoryContext(): void {
    const previousKey = this.historyContextStack.pop()

    if (previousKey && this.contexts.has(previousKey)) {
      this.activeHistoryContextKey = previousKey
      this.restoreUrlFromVirtualStack(previousKey)
    } else {
      this.activeHistoryContextKey = null
    }

    console.log('[MultiRouterHistory] fallbackToPreviousHistoryContext', {
      to: this.activeHistoryContextKey,
    })
  }

  private createInitialVirtualStack(initialLocation?: string): VirtualStack {
    return {
      entries: [{ location: initialLocation ?? this.baseHistory.location, state: {} }],
      position: 0,
    }
  }

  private saveCurrentUrlToVirtualStack(contextKey: string): void {
    const context = this.contexts.get(contextKey)
    if (!context) return

    const currentEntry = context.virtualStack.entries[context.virtualStack.position]
    if (currentEntry) {
      currentEntry.location = this.baseHistory.location
      currentEntry.state = { ...this.baseHistory.state }
    }
  }

  private restoreUrlFromVirtualStack(contextKey: string): void {
    const context = this.contexts.get(contextKey)
    if (!context) return

    const entry = context.virtualStack.entries[context.virtualStack.position]
    if (entry) {
      this.baseHistory.replace(entry.location, entry.state)
    }
  }

  private handlePopState(to: HistoryLocation, from: HistoryLocation, info: NavigationInformation): void {
    if (this.activeHistoryContextKey) {
      const context = this.contexts.get(this.activeHistoryContextKey)!
      this.updateVirtualStackOnPop(context, to, info.delta)
      this.notifyListeners(this.activeHistoryContextKey, to, from, info)
    }
  }

  private updateVirtualStackOnPop(context: ContextHistoryState, to: HistoryLocation, delta: number): void {
    const newPosition = context.virtualStack.position + delta

    if (newPosition >= 0 && newPosition < context.virtualStack.entries.length) {
      context.virtualStack.position = newPosition
    }
  }

  push(contextKey: string, to: HistoryLocation, data?: HistoryState): void {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[MultiRouterHistory] Context "${contextKey}" not registered`)
    }

    context.virtualStack.entries = context.virtualStack.entries.slice(0, context.virtualStack.position + 1)
    context.virtualStack.entries.push({ location: to, state: data ?? {} })
    context.virtualStack.position = context.virtualStack.entries.length - 1

    if (this.activeHistoryContextKey === contextKey) {
      this.baseHistory.push(to, data)
    }

    console.log('[MultiRouterHistory] push', { contextKey, to, isActive: this.activeHistoryContextKey === contextKey })
  }

  replace(contextKey: string, to: HistoryLocation, data?: HistoryState): void {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[MultiRouterHistory] Context "${contextKey}" not registered`)
    }

    context.virtualStack.entries[context.virtualStack.position] = {
      location: to,
      state: data ?? {},
    }

    if (this.activeHistoryContextKey === contextKey) {
      this.baseHistory.replace(to, data)
    }

    console.log('[MultiRouterHistory] replace', { contextKey, to, isActive: this.activeHistoryContextKey === contextKey })
  }

  go(contextKey: string, delta: number, triggerListeners = true): void {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[MultiRouterHistory] Context "${contextKey}" not registered`)
    }

    if (this.activeHistoryContextKey === contextKey) {
      this.baseHistory.go(delta, triggerListeners)
    } else {
      const newPosition = context.virtualStack.position + delta

      if (newPosition >= 0 && newPosition < context.virtualStack.entries.length) {
        const from = context.virtualStack.entries[context.virtualStack.position].location
        context.virtualStack.position = newPosition
        const to = context.virtualStack.entries[newPosition].location

        if (triggerListeners) {
          this.notifyListeners(contextKey, to, from, {
            type: NavigationType.pop,
            direction: delta < 0 ? NavigationDirection.back : NavigationDirection.forward,
            delta,
          })
        }
      }
    }
  }

  listen(contextKey: string, callback: NavigationCallback): () => void {
    const context = this.contexts.get(contextKey)
    if (!context) {
      throw new Error(`[MultiRouterHistory] Context "${contextKey}" not registered`)
    }

    context.listeners.add(callback)

    return () => {
      context.listeners.delete(callback)
    }
  }

  getContextLocation(contextKey: string): HistoryLocation {
    const context = this.contexts.get(contextKey)
    if (!context) {
      return this.baseHistory.location
    }

    return context.virtualStack.entries[context.virtualStack.position]?.location ?? this.baseHistory.location
  }

  getContextState(contextKey: string): HistoryState {
    const context = this.contexts.get(contextKey)
    if (!context) {
      return this.baseHistory.state
    }

    return context.virtualStack.entries[context.virtualStack.position]?.state ?? {}
  }

  private notifyListeners(
    contextKey: string,
    to: HistoryLocation,
    from: HistoryLocation,
    info: NavigationInformation,
  ): void {
    const context = this.contexts.get(contextKey)
    if (!context) return

    context.listeners.forEach((callback) => {
      callback(to, from, info)
    })
  }

  createHref(location: HistoryLocation): string {
    return this.baseHistory.createHref(location)
  }

  destroy(): void {
    if (this.baseHistoryCleanup) {
      this.baseHistoryCleanup()
      this.baseHistoryCleanup = null
    }

    this.contexts.clear()
    this.baseHistory.destroy()
  }
}
