import { desktopContextKeySymbol } from '@/core/symbols'
import { Plugin } from 'vue'
import type { Router, RouterHistory } from 'vue-router'

type HistoryLocation = string
type HistoryState = Record<string, any>
// type NavigationCallback = (
//   to: HistoryLocation,
//   from: HistoryLocation,
//   information: { delta: number; type: 'pop' | 'push' },
// ) => void
enum NavigationType {
  pop = 'pop',
  push = 'push',
}
enum NavigationDirection {
  back = 'back',
  forward = 'forward',
  unknown = '',
}
interface NavigationInformation {
  type: NavigationType
  direction: NavigationDirection
  delta: number
}
interface NavigationCallback {
  (to: HistoryLocation, from: HistoryLocation, information: NavigationInformation): void
}

// Callback for syncing browser URL - set externally
let syncUrlCallback: (() => void) | null = null

export function setSyncUrlCallback(callback: () => void): void {
  syncUrlCallback = callback
}

interface ContextState {
  location: HistoryLocation
  state: HistoryState
  stack: Array<{ location: HistoryLocation; state: HistoryState }>
  position: number
}

export interface ContextManager {
  active: ShallowRef<
    | {
        key: string
      }
    | undefined
  >
  routerPlugin(router: Router): Plugin
}

/**
 * Creates a multi-context history that maintains separate routing state for each context.
 * Each context (including main) has its own virtual history stack.
 */
export function createMultiContextHistory(
  baseHistoryBuilder: () => RouterHistory,
  contextManager: ContextManager,
): RouterHistory & {
  getContextLocation: (contextKey: string) => HistoryLocation
  initContext: (contextKey: string, initialLocation?: HistoryLocation) => void
  destroyContext: (contextKey: string) => void
  callWithContextKey: (contextKey: string, fn: () => void) => void
  onRouterResolved: (router: Router) => void
} {
  const baseHistory = baseHistoryBuilder()
  // Virtual state for all contexts
  const contextStates = new Map<string, ContextState>()

  // Listeners per context
  const contextListeners = new Map<string, Set<NavigationCallback>>()

  function getOrCreateContext(key: string): ContextState {
    if (key === 'main') {
      throw new Error('main context is not allowed')
    }

    if (!contextStates.has(key)) {
      const initialLocation = '/'
      contextStates.set(key, {
        location: initialLocation,
        state: {},
        stack: [{ location: initialLocation, state: {} }],
        position: 0,
      })
      console.log('initContext', key, initialLocation)
    }
    return contextStates.get(key)!
  }

  function notifyListeners(
    contextKey: string,
    to: HistoryLocation,
    from: HistoryLocation,
    information: NavigationInformation,
  ) {
    const listeners = contextListeners.get(contextKey)
    if (listeners) {
      listeners.forEach((cb) =>
        multiHistory.callWithContextKey(contextKey, () => cb(to, from, information)),
      )
    }
  }

  const isMainContext = (val: string) => val === 'main'

  let started = false

  let currentContextKey: string | null = null

  // noinspection UnnecessaryLocalVariableJS
  const multiHistory: RouterHistory & {
    getContextLocation: (contextKey: string) => HistoryLocation
    initContext: (contextKey: string, initialLocation?: HistoryLocation) => void
    destroyContext: (contextKey: string) => void
    callWithContextKey: (contextKey: string, fn: () => void) => void
  } = {
    get base() {
      console.log('[MultiContextHistory] get base', { currentContextKey })
      return baseHistory.base
    },

    get location(): HistoryLocation {
      console.log('[MultiContextHistory] get location', { currentContextKey })
      return baseHistory.location
      // const ctx = getOrCreateContext(currentContextKey)
      // return ctx.location
    },

    get state(): HistoryState {
      console.log('[MultiContextHistory] get state', { currentContextKey })
      return baseHistory.state
      // const ctx = getOrCreateContext(currentContextKey)
      // return ctx.state
    },

    push(to, data?): void {
      const contextKey = data?.[desktopContextKeySymbol as any] as string | undefined

      console.log('[MultiContextHistory] push', { to, data, contextKey, currentContextKey })
      if (!contextKey) {
        throw new Error('[MultiContextHistory] push called without contextKey')
      }
      if (isMainContext(contextKey)) {
        return baseHistory.push(to, data)
      }

      const ctx = getOrCreateContext(contextKey)
      const from = ctx.location
      // Truncate forward history if we're not at the end
      ctx.stack = ctx.stack.slice(0, ctx.position + 1)

      // Push new entry
      ctx.stack.push({ location: to, state: data ?? {} })
      ctx.position = ctx.stack.length - 1
      ctx.location = to
      ctx.state = data ?? {}

      notifyListeners(contextKey, to, from, {
        type: NavigationType.push,
        direction: NavigationDirection.forward,
        delta: 1,
      })

      // Если мы не в main контексте - создаём memoryHistory и храним в ней.
      // Изучить как работает history в роутере, связан ли он напрямую с браузером
      // let contextKey: string | undefined
      // if (typeof to !== 'object') {
      //   console.warn(
      //     '[MultiContextHistory] push called with non-object, unable to resolve contextKey',
      //     to,
      //   )
      // } else {
      //   contextKey = (to as MaybeHasContextKey).contextKey
      //
      //   if (!contextKey) {
      //     contextKey = currentContextKey
      //     console.warn(
      //       `[MultiContextHistory] push called with object without contextKey, using current context (${contextKey})`,
      //       to,
      //     )
      //   }
      // }
      // if (!to.contextKey)
      // if (typeof to !== 'object') {

      // return baseHistory.push(to, data)

      // }
      // const contextKey = currentContextKey
      // const ctx = getOrCreateContext(contextKey)
      // const from = ctx.location
      //
      // // Truncate forward history if we're not at the end
      // ctx.stack = ctx.stack.slice(0, ctx.position + 1)
      //
      // // Push new entry
      // ctx.stack.push({ location: to, state: data ?? {} })
      // ctx.position = ctx.stack.length - 1
      // ctx.location = to
      // ctx.state = data ?? {}
      //
      // notifyListeners(contextKey, to, from, 1, 'push')
      //
      // // Sync browser URL after push
      // if (syncUrlCallback) {
      //   syncUrlCallback()
      // }
    },

    replace(to: HistoryLocation, data?: HistoryState): void {
      let contextKey = data?.[desktopContextKeySymbol as any] as string | undefined
      if (!started) {
        started = true
        contextKey ??= 'main'
      }

      if (!contextKey) {
        throw new Error('[MultiContextHistory] replace called without contextKey')
      }
      console.log('[MultiContextHistory] replace', { to, data, contextKey, currentContextKey })
      if (isMainContext(contextKey)) {
        return baseHistory.replace(to, data)
      }

      const ctx = getOrCreateContext(contextKey)
      const from = ctx.location

      ctx.stack[ctx.position] = {
        location: to,
        state: data ?? {},
      }

      ctx.location = to
      ctx.state = data ?? {}

      notifyListeners(contextKey, to, from, {
        type: NavigationType.push,
        direction: NavigationDirection.forward,
        delta: 0,
      })

      // if (iss
      // const contextKey = currentContextKey
      // const ctx = getOrCreateContext(contextKey)
      // const from = ctx.location
      //
      // // If 'to' starts with '?' it's a query-only update, preserve the path
      // let fullLocation = to
      // if (to.startsWith('?')) {
      //   // Extract path from current location (everything before '?')
      //   const currentPath = from.split('?')[0]
      //   fullLocation = currentPath + to
      // }
      //
      // // Replace current entry
      // ctx.stack[ctx.position] = { location: fullLocation, state: data ?? {} }
      // ctx.location = fullLocation
      // ctx.state = data ?? {}
      //
      // // notifyListeners(contextKey, fullLocation, from, 0, 'push')
      //
      // // Sync browser URL after replace
      // if (syncUrlCallback) {
      //   syncUrlCallback()
      // }
    },

    go(delta: number, triggerListeners = true): void {
      if (!currentContextKey) {
        throw new Error('[MultiContextHistory] go called without contextKey')
      }
      console.log('[MultiContextHistory] go', { delta, triggerListeners, currentContextKey })
      // const ctx = getOrCreateContext(contextKey)
      // if (isMainContext()) {
      //   baseHistory.go(delta, triggerListeners)
      // }
      baseHistory.go(delta, triggerListeners)
      // const from = ctx.location
      //
      // const newPosition = ctx.position + delta
      // if (newPosition < 0 || newPosition >= ctx.stack.length) {
      //   return // Can't go beyond bounds
      // }
      //
      // ctx.position = newPosition
      // const entry = ctx.stack[newPosition]
      // ctx.location = entry.location
      // ctx.state = entry.state
      //
      // if (triggerListeners) {
      //   notifyListeners(contextKey, ctx.location, from, delta, 'pop')
      // }
    },

    listen(callback): () => void {
      const contextKey = currentContextKey ?? 'main'
      console.log('[MultiContextHistory] listen', { currentContextKey, contextKey })

      if (isMainContext(contextKey)) {
        return baseHistory.listen(callback)
      }

      if (!contextListeners.has(contextKey)) {
        contextListeners.set(contextKey, new Set())
      }
      contextListeners.get(contextKey)!.add(callback)

      return () => {
        contextListeners.get(contextKey)?.delete(callback)
      }
      // if (!currentContextKey) {
      //   // debugger
      //   // throw new Error('[MultiContextHistory] listen called without contextKey')
      //   console.warn('[MultiContextHistory] listen called without contextKey')
      // }
      // if (!contextListeners.has(currentContextKey)) {
      //   contextListeners.set(currentContextKey, new Set())
      // }
      // contextListeners.get(currentContextKey)!.add(callback)
      //
      // return () => {
      //   contextListeners.get(currentContextKey)?.delete(callback)
      // }
    },

    createHref(location: HistoryLocation): string {
      // if (!currentContextKey && started) {
      //   debugger
      //   throw new Error('[MultiContextHistory] createHref called without contextKey')
      // }

      return baseHistory.createHref(location)
    },

    destroy(): void {
      baseHistory.destroy()
      contextStates.clear()
      contextListeners.clear()
    },

    // Extended API for context management
    getContextLocation(contextKey: string): HistoryLocation {
      const location = getOrCreateContext(contextKey).location
      return location
    },

    initContext(contextKey: string, initialLocation: HistoryLocation = '/'): void {
      contextStates.set(contextKey, {
        location: initialLocation,
        state: {},
        stack: [{ location: initialLocation, state: {} }],
        position: 0,
      })
    },

    destroyContext(contextKey: string): void {
      contextStates.delete(contextKey)
      contextListeners.delete(contextKey)
    },

    callWithContextKey(contextKey: string, fn: () => void): void {
      const prev = currentContextKey
      currentContextKey = contextKey
      try {
        return fn()
      } finally {
        currentContextKey = prev
      }
    },

    onRouterResolved(router: Router) {
      Object.assign(router, {
        callWithContextKey: this.callWithContextKey,
      })
    },
  }

  return multiHistory
}
