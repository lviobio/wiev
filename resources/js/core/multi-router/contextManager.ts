import {
  mapMaybePromise,
  MultiRouterHistoryManager,
  MultiRouterHistoryManagerOptions,
  type HistoryBuilder,
  type MaybePromise,
} from '@/core/multi-router/history'
import type { ContextTypes } from '@/core/multi-router/index'
import { App, shallowRef } from 'vue'
import { Router, RouterHistory } from 'vue-router'

type ContextInterface = {
  type: string
  router: Router
  history: RouterHistory
  initialized: boolean
  historyEnabled: boolean
}

interface ActiveContextInterface {
  key: string
  context: ContextInterface
}

type ContextInitListener = (key: string) => void

type MakeRouterFn = (contextKey: string, history: RouterHistory) => Router

type HistoryManagerOptions = {
  historyBuilder: HistoryBuilder
} & MultiRouterHistoryManagerOptions

export class MultiRouterManagerInstance {
  private started = false
  private activeContext = shallowRef<ActiveContextInterface>()
  private activeHistoryContext = shallowRef<ActiveContextInterface>()
  private registered: Map<string, ContextInterface> = new Map()
  private onContextInitListeners: ContextInitListener[] = []
  private readonly historyManager: MultiRouterHistoryManager

  constructor(
    private app: App,
    private types: ContextTypes,
    historyManagerOptions: HistoryManagerOptions,
    private makeRouter: MakeRouterFn,
  ) {
    const { historyBuilder, ...historyOptions } = historyManagerOptions
    this.historyManager = new MultiRouterHistoryManager(historyBuilder, {
      ...historyOptions,
      onContextActivate: (contextKey) => {
        // Activate context on popstate (browser back/forward)
        // Use updateHistory=false since we're responding to browser history change
        this.setActive(contextKey, false)
      },
    })
  }

  getHistoryManager() {
    return this.historyManager
  }

  getActiveContext() {
    return this.activeContext.value
  }

  getActiveContextRef() {
    return this.activeContext
  }

  getActiveHistoryContext() {
    return this.activeHistoryContext.value
  }

  getActiveHistoryContextRef() {
    return this.activeHistoryContext
  }

  setActive(key: string, updateHistory: boolean) {
    const item = this.registered.get(key)

    if (!item) throw new Error(`[MultiRouter] Context "${key}" not found`)

    let modified = false

    if (this.activeContext.value?.key !== key) {
      this.activeContext.value = {
        key,
        context: item,
      }

      modified = true
    }

    if (updateHistory && this.activeHistoryContext.value?.key !== key) {
      // Always save active context to storage (for restoration after reload)
      // But only update browser history if historyEnabled is true
      if (item.historyEnabled) {
        this.activeHistoryContext.value = this.activeContext.value
        this.historyManager.setActiveHistoryContext(key)
      } else {
        // Just save to storage without updating browser history
        this.historyManager.saveActiveContext(key)
      }
    }

    if (modified) {
      console.log('[MultiRouterContextManager] setActive', {
        key,
        updateHistory,
        historyEnabled: item.historyEnabled,
      })
    }

    return modified
  }

  clearHistoryContext(key: string) {
    if (this.activeHistoryContext.value?.key === key) {
      this.historyManager.clearActiveHistoryContext(key)

      const newActiveKey = this.historyManager.getActiveHistoryContextKey()
      if (newActiveKey) {
        const newContext = this.registered.get(newActiveKey)
        if (newContext) {
          this.activeHistoryContext.value = { key: newActiveKey, context: newContext }
        }
      } else {
        this.activeHistoryContext.value = undefined
      }
    }
  }

  markAsStarted() {
    this.started = true
  }

  getRouter(key: string) {
    return this.registered.get(key)!.router
  }

  has(key: string) {
    return this.registered.has(key)
  }

  public register(
    type: string,
    key: string,
    options?: { location?: string; initialLocation?: string; historyEnabled?: boolean },
  ): MaybePromise<void> {
    const typeConfig = this.types[type]

    if (!typeConfig) throw new Error(`[MultiRouter] Context type "${type}" not found`)

    const historyEnabled = options?.historyEnabled ?? true

    const historyResult = this.historyManager.createContextHistory(key, {
      location: options?.location,
      initialLocation: options?.initialLocation,
      historyEnabled,
    })

    return mapMaybePromise(historyResult, (history) => {
      const router = this.makeRouter(key, history)

      this.registered.set(key, {
        type,
        router,
        history,
        initialized: false,
        historyEnabled,
      })

      router.push(history.location).catch((err) => {
        console.warn('Unexpected error when starting the router:', err)
      })

      router.isReady().then(() => {
        this.markAsStarted()

        // Auto-activate if this was the last active context before reload
        const lastActiveKey = this.historyManager.getLastActiveContextKey()
        mapMaybePromise(lastActiveKey, (resolvedLastActiveKey) => {
          if (resolvedLastActiveKey === key && !this.activeHistoryContext.value) {
            console.log('[MultiRouterContextManager] Auto-activating last active context', { key })
            this.setActive(key, true)
          }
        })
      })
    })
  }

  getContextLocation(key: string): string | undefined {
    return this.historyManager.getContextLocation(key)
  }

  getContextHistoryEnabled(key: string): boolean {
    return this.registered.get(key)?.historyEnabled ?? true
  }

  public unregister(key: string) {
    const context = this.registered.get(key)
    if (context) {
      console.log('[MultiRouterContextManager] unregister', { key })
      this.historyManager.removeContextHistory(key)
      this.registered.delete(key)
    }
  }

  public initialize(key: string) {
    this.registered.get(key)!.initialized = true
    this.onContextInitListeners.forEach((fn) => fn(key))
  }

  onContextInit(fn: ContextInitListener) {
    if (this.started) {
      throw new Error('[MultiRouter] adding listener after start is not allowed')
    }

    this.onContextInitListeners.push(fn)
  }
}
