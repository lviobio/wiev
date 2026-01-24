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

const CONTEXT_KEY_STATE = '__multiRouterContext'
const STACK_INDEX_STATE = '__multiRouterStackIndex'
const STORAGE_KEY = '__multiRouterVirtualStacks'
const ACTIVE_CONTEXT_STORAGE_KEY = '__multiRouterActiveContext'

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
      // Check if this was the last active context - if so, use browser URL
      const lastActiveContext = this.getStoredActiveContext()
      const isLastActive = lastActiveContext === contextKey
      
      let virtualStack: VirtualStack
      
      if (isLastActive) {
        // This was the last active context - use browser URL as priority
        // This handles the case where user manually changed the URL
        const browserUrl = this.baseHistory.location
        const restoredStack = this.restoreVirtualStack(contextKey)
        
        if (restoredStack) {
          // Update the current position with browser URL
          restoredStack.entries[restoredStack.position] = {
            location: browserUrl,
            state: this.baseHistory.state ?? {},
          }
          virtualStack = restoredStack
        } else {
          virtualStack = this.createInitialVirtualStack(browserUrl)
        }
        
        console.log('[MultiRouterHistory] Restored last active context with browser URL', {
          contextKey,
          browserUrl,
        })
      } else {
        // Not the last active context - restore from sessionStorage or use initial
        virtualStack = this.restoreVirtualStack(contextKey) ?? this.createInitialVirtualStack(initialLocation)
      }
      
      this.contexts.set(contextKey, {
        virtualStack,
        listeners: new Set(),
      })
    }

    return new ContextHistoryProxy(contextKey, this)
  }

  removeContextHistory(contextKey: string): void {
    this.contexts.delete(contextKey)
    this.clearStoredVirtualStack(contextKey)

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
      this.historyContextStack = this.historyContextStack.filter((k) => k !== previousKey)
      this.historyContextStack.push(previousKey)
    }

    this.activeHistoryContextKey = contextKey
    this.saveActiveContext(contextKey)
    
    // Update browser URL to show the new context's current location
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
    } else {
      this.activeHistoryContextKey = null
    }

    console.log('[MultiRouterHistory] fallbackToPreviousHistoryContext', {
      to: this.activeHistoryContextKey,
    })
  }

  private createInitialVirtualStack(initialLocation?: string): VirtualStack {
    // Use explicit initialLocation, or fallback to base path (not current browser URL)
    // This prevents new contexts from inheriting query params from other contexts
    const location = initialLocation ?? '/'
    return {
      entries: [{ location, state: {} }],
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
      this.baseHistory.replace(entry.location, { 
        ...entry.state, 
        [CONTEXT_KEY_STATE]: contextKey,
        [STACK_INDEX_STATE]: context.virtualStack.position,
      })
    }
  }

  private handlePopState(to: HistoryLocation, from: HistoryLocation, info: NavigationInformation): void {
    // Get context and stack index from browser history state (this is the TARGET entry)
    const stateContextKey = this.baseHistory.state?.[CONTEXT_KEY_STATE] as string | undefined
    const stateStackIndex = this.baseHistory.state?.[STACK_INDEX_STATE] as number | undefined
    
    console.log('[MultiRouterHistory] popstate raw', {
      stateContextKey,
      stateStackIndex,
      browserTo: to,
      browserFrom: from,
      delta: info.delta,
      virtualStacks: Object.fromEntries(
        Array.from(this.contexts.entries()).map(([k, v]) => [
          k, 
          { position: v.virtualStack.position, entries: v.virtualStack.entries.map(e => e.location) }
        ])
      ),
    })
    
    // For back navigation: we need to find which context we're LEAVING (source)
    // For forward navigation: we use the state context (target)
    let ownerContextKey: string | null = null
    let targetStackIndex: number | null = null
    
    if (info.delta < 0) {
      // Going back - find which context owns the 'from' URL (the one we're leaving)
      // This is the context that made the last push
      for (const [contextKey, context] of this.contexts) {
        const currentEntry = context.virtualStack.entries[context.virtualStack.position]
        if (currentEntry && currentEntry.location === from) {
          ownerContextKey = contextKey
          break
        }
      }
      // After reload, virtual stacks are empty, so use state context as fallback
      if (!ownerContextKey && stateContextKey && this.contexts.has(stateContextKey)) {
        ownerContextKey = stateContextKey
        targetStackIndex = stateStackIndex ?? null
      }
    } else {
      // Going forward - use state context (where we're going)
      if (stateContextKey && this.contexts.has(stateContextKey)) {
        ownerContextKey = stateContextKey
        targetStackIndex = stateStackIndex ?? null
      }
    }
    
    // Final fallback to active context
    if (!ownerContextKey) {
      ownerContextKey = this.activeHistoryContextKey
    }

    if (!ownerContextKey) return

    const context = this.contexts.get(ownerContextKey)!
    
    // Calculate new position in virtual stack
    let newPosition: number
    if (targetStackIndex !== null) {
      // Use stack index from state (works after reload)
      newPosition = targetStackIndex
    } else {
      // Calculate from delta (works during session)
      newPosition = context.virtualStack.position + info.delta
    }
    
    // Ensure virtual stack has enough entries (after reload, stack might be smaller)
    while (context.virtualStack.entries.length <= newPosition) {
      context.virtualStack.entries.push({ location: to, state: {} })
    }
    
    if (newPosition >= 0 && newPosition < context.virtualStack.entries.length) {
      const previousLocation = context.virtualStack.entries[context.virtualStack.position]?.location ?? from
      context.virtualStack.position = newPosition
      
      // Get the target URL from the context's virtual stack (not from browser!)
      // The browser URL belongs to a different context
      const targetLocation = context.virtualStack.entries[newPosition]?.location ?? to

      console.log('[MultiRouterHistory] popstate result', {
        ownerContext: ownerContextKey,
        activeContext: this.activeHistoryContextKey,
        browserUrl: to,
        contextUrl: targetLocation,
        targetStackIndex: newPosition,
        previousLocation,
        delta: info.delta,
      })

      // Notify with the context's own URL from its virtual stack
      this.notifyListeners(ownerContextKey, targetLocation, previousLocation, info)
      
      // Save updated position to sessionStorage
      this.saveVirtualStack(ownerContextKey)
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
      this.baseHistory.push(to, { 
        ...data, 
        [CONTEXT_KEY_STATE]: contextKey,
        [STACK_INDEX_STATE]: context.virtualStack.position,
      })
    }

    console.log('[MultiRouterHistory] push', { contextKey, to, stackIndex: context.virtualStack.position, isActive: this.activeHistoryContextKey === contextKey })
    
    this.saveVirtualStack(contextKey)
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
      this.baseHistory.replace(to, { 
        ...data, 
        [CONTEXT_KEY_STATE]: contextKey,
        [STACK_INDEX_STATE]: context.virtualStack.position,
      })
    }

    console.log('[MultiRouterHistory] replace', { contextKey, to, stackIndex: context.virtualStack.position, isActive: this.activeHistoryContextKey === contextKey })
    
    this.saveVirtualStack(contextKey)
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

  // SessionStorage persistence methods
  
  private saveVirtualStack(contextKey: string): void {
    try {
      const context = this.contexts.get(contextKey)
      if (!context) return

      const allStacks = this.getAllStoredStacks()
      allStacks[contextKey] = {
        entries: context.virtualStack.entries,
        position: context.virtualStack.position,
      }
      
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(allStacks))
    } catch (e) {
      console.warn('[MultiRouterHistory] Failed to save virtual stack to sessionStorage:', e)
    }
  }

  private restoreVirtualStack(contextKey: string): VirtualStack | null {
    try {
      const allStacks = this.getAllStoredStacks()
      const stored = allStacks[contextKey]
      
      if (stored && Array.isArray(stored.entries) && stored.entries.length > 0) {
        console.log('[MultiRouterHistory] Restored virtual stack from sessionStorage', {
          contextKey,
          entries: stored.entries.length,
          position: stored.position,
        })
        return {
          entries: stored.entries,
          position: stored.position ?? 0,
        }
      }
    } catch (e) {
      console.warn('[MultiRouterHistory] Failed to restore virtual stack from sessionStorage:', e)
    }
    return null
  }

  private clearStoredVirtualStack(contextKey: string): void {
    try {
      const allStacks = this.getAllStoredStacks()
      delete allStacks[contextKey]
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(allStacks))
    } catch (e) {
      console.warn('[MultiRouterHistory] Failed to clear virtual stack from sessionStorage:', e)
    }
  }

  private getAllStoredStacks(): Record<string, { entries: Array<{ location: string; state: any }>; position: number }> {
    try {
      const stored = sessionStorage.getItem(STORAGE_KEY)
      if (stored) {
        return JSON.parse(stored)
      }
    } catch (e) {
      // ignore
    }
    return {}
  }

  private saveActiveContext(contextKey: string): void {
    try {
      sessionStorage.setItem(ACTIVE_CONTEXT_STORAGE_KEY, contextKey)
    } catch (e) {
      console.warn('[MultiRouterHistory] Failed to save active context to sessionStorage:', e)
    }
  }

  private getStoredActiveContext(): string | null {
    try {
      return sessionStorage.getItem(ACTIVE_CONTEXT_STORAGE_KEY)
    } catch (e) {
      return null
    }
  }

  getLastActiveContextKey(): string | null {
    return this.getStoredActiveContext()
  }
}
