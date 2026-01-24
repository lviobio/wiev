import type { RouterHistory } from 'vue-router'
import { ContextHistoryProxy } from './context-proxy'
import {
  type ContextStorageAdapter,
  type MaybePromise,
  flatMapMaybePromise,
  mapMaybePromise,
} from './storage/index'
import {
  NavigationDirection,
  NavigationType,
  type HistoryBuilder,
  type HistoryLocation,
  type HistoryState,
  type NavigationCallback,
  type NavigationInformation,
  type VirtualStack,
} from './types'
import { VirtualStackManager } from './virtual-stack'

const CONTEXT_KEY_STATE = '__multiRouterContext'
const STACK_INDEX_STATE = '__multiRouterStackIndex'

export interface MultiRouterHistoryManagerOptions {
  storageAdapter?: ContextStorageAdapter
}

export class MultiRouterHistoryManager {
  private baseHistory: RouterHistory
  private stacks: VirtualStackManager
  private activeHistoryContextKey: string | null = null
  private historyContextStack: string[] = []
  private baseHistoryCleanup: (() => void) | null = null

  constructor(historyBuilder: HistoryBuilder, options?: MultiRouterHistoryManagerOptions) {
    this.baseHistory = historyBuilder()
    this.stacks = new VirtualStackManager(options?.storageAdapter)
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

  createContextHistory(
    contextKey: string,
    options?: { location?: string; initialLocation?: string },
  ): MaybePromise<RouterHistory> {
    if (this.stacks.has(contextKey)) {
      return new ContextHistoryProxy(contextKey, this)
    }

    const { location, initialLocation } = options ?? {}

    // Get last active context (may be sync or async)
    return flatMapMaybePromise(this.stacks.getStoredActiveContext(), (lastActiveContext) => {
      const isLastActive = lastActiveContext === contextKey

      if (location) {
        // Explicit location always has priority - force this URL
        const virtualStack = this.createInitialVirtualStack(location)
        console.log('[MultiRouterHistory] Created context with forced location', {
          contextKey,
          location,
        })
        return this.finalizeContextCreation(contextKey, virtualStack, isLastActive)
      }

      // No forced location - try to restore from storage
      return mapMaybePromise(this.stacks.restore(contextKey), (restoredStack) => {
        let virtualStack: VirtualStack

        if (restoredStack) {
          // Storage has priority over initialLocation
          if (isLastActive) {
            // Update current position with browser URL (user may have changed it)
            const browserUrl = this.baseHistory.location
            restoredStack.entries[restoredStack.position] = {
              location: browserUrl,
              state: this.baseHistory.state ?? {},
            }
            console.log('[MultiRouterHistory] Restored from storage with browser URL', {
              contextKey,
              browserUrl,
            })
          } else {
            console.log('[MultiRouterHistory] Restored from storage', { contextKey })
          }
          virtualStack = restoredStack
        } else if (isLastActive) {
          // No storage, but was last active - use browser URL
          const browserUrl = this.baseHistory.location
          virtualStack = this.createInitialVirtualStack(browserUrl)
          console.log('[MultiRouterHistory] Created with browser URL (last active)', {
            contextKey,
            browserUrl,
          })
        } else if (initialLocation) {
          // Use initialLocation as fallback
          virtualStack = this.createInitialVirtualStack(initialLocation)
          console.log('[MultiRouterHistory] Created with initialLocation', {
            contextKey,
            initialLocation,
          })
        } else {
          // Fallback to default '/'
          virtualStack = this.createInitialVirtualStack()
          console.log('[MultiRouterHistory] Created with default location', { contextKey })
        }

        return this.finalizeContextCreation(contextKey, virtualStack, isLastActive)
      })
    })
  }

  private finalizeContextCreation(
    contextKey: string,
    virtualStack: VirtualStack,
    isLastActive: boolean,
  ): RouterHistory {
    this.stacks.create(contextKey, virtualStack)

    // If this context was the last active, restore its active state
    if (isLastActive) {
      this.activeHistoryContextKey = contextKey
      this.restoreUrlFromVirtualStack(contextKey)
    }

    return new ContextHistoryProxy(contextKey, this)
  }

  removeContextHistory(contextKey: string): void {
    this.stacks.remove(contextKey)

    if (this.activeHistoryContextKey === contextKey) {
      this.fallbackToPreviousHistoryContext()
    }

    this.historyContextStack = this.historyContextStack.filter((k) => k !== contextKey)
  }

  setActiveHistoryContext(contextKey: string): void {
    if (!this.stacks.has(contextKey)) {
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
    this.stacks.saveActiveContext(contextKey)

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

    if (previousKey && this.stacks.has(previousKey)) {
      this.activeHistoryContextKey = previousKey
    } else {
      this.activeHistoryContextKey = null
    }

    console.log('[MultiRouterHistory] fallbackToPreviousHistoryContext', {
      to: this.activeHistoryContextKey,
    })
  }

  private createInitialVirtualStack(initialLocation?: string): VirtualStack {
    const location = initialLocation ?? '/'
    return {
      entries: [{ location, state: {} }],
      position: 0,
    }
  }

  private restoreUrlFromVirtualStack(contextKey: string): void {
    const context = this.stacks.get(contextKey)
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

  private handlePopState(
    to: HistoryLocation,
    from: HistoryLocation,
    info: NavigationInformation,
  ): void {
    const stateContextKey = this.baseHistory.state?.[CONTEXT_KEY_STATE] as string | undefined
    const stateStackIndex = this.baseHistory.state?.[STACK_INDEX_STATE] as number | undefined

    console.log('[MultiRouterHistory] popstate raw', {
      stateContextKey,
      stateStackIndex,
      browserTo: to,
      browserFrom: from,
      delta: info.delta,
      virtualStacks: Object.fromEntries(
        Array.from(this.stacks.entries()).map(([k, v]) => [
          k,
          {
            position: v.virtualStack.position,
            entries: v.virtualStack.entries.map((e) => e.location),
          },
        ]),
      ),
    })

    let ownerContextKey: string | null = null
    let targetStackIndex: number | null = null

    if (info.delta < 0) {
      // Going back - find which context owns the 'from' URL
      for (const [contextKey, context] of this.stacks.entries()) {
        const currentEntry = context.virtualStack.entries[context.virtualStack.position]
        if (currentEntry && currentEntry.location === from) {
          ownerContextKey = contextKey
          break
        }
      }
      // After reload, use state context as fallback
      if (!ownerContextKey && stateContextKey && this.stacks.has(stateContextKey)) {
        ownerContextKey = stateContextKey
        targetStackIndex = stateStackIndex ?? null
      }
    } else {
      // Going forward - use state context
      if (stateContextKey && this.stacks.has(stateContextKey)) {
        ownerContextKey = stateContextKey
        targetStackIndex = stateStackIndex ?? null
      }
    }

    if (!ownerContextKey) {
      ownerContextKey = this.activeHistoryContextKey
    }

    if (!ownerContextKey) return

    const context = this.stacks.get(ownerContextKey)!

    let newPosition: number
    if (targetStackIndex !== null) {
      newPosition = targetStackIndex
    } else {
      newPosition = context.virtualStack.position + info.delta
    }

    // Ensure virtual stack has enough entries
    this.stacks.ensureEntriesUpTo(ownerContextKey, newPosition, to)

    if (newPosition >= 0 && newPosition < context.virtualStack.entries.length) {
      const previousLocation =
        context.virtualStack.entries[context.virtualStack.position]?.location ?? from
      this.stacks.setPosition(ownerContextKey, newPosition)

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

      this.stacks.notifyListeners(ownerContextKey, targetLocation, previousLocation, info)
    }
  }

  push(contextKey: string, to: HistoryLocation, data?: HistoryState): void {
    const stackIndex = this.stacks.push(contextKey, to, data ?? {})

    if (this.activeHistoryContextKey === contextKey) {
      this.baseHistory.push(to, {
        ...data,
        [CONTEXT_KEY_STATE]: contextKey,
        [STACK_INDEX_STATE]: stackIndex,
      })
    }

    console.log('[MultiRouterHistory] push', {
      contextKey,
      to,
      stackIndex,
      isActive: this.activeHistoryContextKey === contextKey,
    })
  }

  replace(contextKey: string, to: HistoryLocation, data?: HistoryState): void {
    const stackIndex = this.stacks.replace(contextKey, to, data ?? {})

    if (this.activeHistoryContextKey === contextKey) {
      this.baseHistory.replace(to, {
        ...data,
        [CONTEXT_KEY_STATE]: contextKey,
        [STACK_INDEX_STATE]: stackIndex,
      })
    }

    console.log('[MultiRouterHistory] replace', {
      contextKey,
      to,
      stackIndex,
      isActive: this.activeHistoryContextKey === contextKey,
    })
  }

  go(contextKey: string, delta: number, triggerListeners = true): void {
    if (!this.stacks.has(contextKey)) {
      throw new Error(`[MultiRouterHistory] Context "${contextKey}" not registered`)
    }

    if (this.activeHistoryContextKey === contextKey) {
      this.baseHistory.go(delta, triggerListeners)
    } else {
      const result = this.stacks.navigate(contextKey, delta)
      if (result && triggerListeners) {
        this.stacks.notifyListeners(contextKey, result.to, result.from, {
          type: NavigationType.pop,
          direction: delta < 0 ? NavigationDirection.back : NavigationDirection.forward,
          delta,
        })
      }
    }
  }

  listen(contextKey: string, callback: NavigationCallback): () => void {
    return this.stacks.addListener(contextKey, callback)
  }

  getContextLocation(contextKey: string): HistoryLocation {
    return this.stacks.getLocation(contextKey, this.baseHistory.location)
  }

  getContextState(contextKey: string): HistoryState {
    return this.stacks.getState(contextKey)
  }

  createHref(location: HistoryLocation): string {
    return this.baseHistory.createHref(location)
  }

  destroy(): void {
    if (this.baseHistoryCleanup) {
      this.baseHistoryCleanup()
      this.baseHistoryCleanup = null
    }

    this.stacks.clear()
    this.baseHistory.destroy()
  }

  getLastActiveContextKey(): MaybePromise<string | null> {
    return this.stacks.getStoredActiveContext()
  }
}
