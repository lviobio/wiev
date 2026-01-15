import { getCurrentContextKey } from '@/core/sub-router/index'
import { contextKeySymbol } from '@/core/sub-router/symbols'
import type { RouterHistory } from 'vue-router'

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

/**
 * Getting contextKey that previously saved in history state with push/replace decorated calls
 */
function getCurrentContextKeyFromState(data?: HistoryState): string | undefined {
  return data?.[contextKeySymbol as any] as string | undefined
}

export function createHistoryWrapper(baseHistoryBuilder: () => RouterHistory): RouterHistory {
  const baseHistory = baseHistoryBuilder()
  const isMainContext = (val: string) => val === 'main'
  let started = false

  return {
    get base() {
      console.log('[MultiContextHistory] get base', { currentContextKey: getCurrentContextKey() })
      return baseHistory.base
    },

    get location(): HistoryLocation {
      console.log('[MultiContextHistory] get location', {
        currentContextKey: getCurrentContextKey(),
      })
      return baseHistory.location
    },

    get state(): HistoryState {
      console.log('[MultiContextHistory] get state', { currentContextKey: getCurrentContextKey() })
      return baseHistory.state
    },

    push(to, data): void {
      const currentContextKey = getCurrentContextKeyFromState(data)

      console.log('[MultiContextHistory] push', { to, data, currentContextKey })
      if (!currentContextKey) {
        throw new Error('[MultiContextHistory] push called without contextKey')
      }
      return baseHistory.push(to, data)
      // if (isMainContext(contextKey)) {
      //   return baseHistory.push(to, data)
      // }
      //
      // const ctx = getOrCreateContext(contextKey)
      // const from = ctx.location
      // // Truncate forward history if we're not at the end
      // ctx.stack = ctx.stack.slice(0, ctx.position + 1)
      //
      // // Push new entry
      // ctx.stack.push({ location: to, state: data ?? {} })
      // ctx.position = ctx.stack.length - 1
      // ctx.location = to
      // ctx.state = data ?? {}
      //
      // notifyListeners(contextKey, to, from, {
      //   type: NavigationType.push,
      //   direction: NavigationDirection.forward,
      //   delta: 1,
      // })
    },

    replace(to: HistoryLocation, data?: HistoryState): void {
      const currentContextKey = getCurrentContextKeyFromState(data)

      //TODO: когда started = false, и currentContextKey undefined, то нужно брать последний активный контекст

      //currentContextKey is undefined when it's first navigation (isFirstNavigation = from === START_LOCATION_NORMALIZED)
      //so we need to set started flag, to prevent error on first navigation
      if (!started) {
        started = true
      } else if (!currentContextKey) {
        throw new Error('[MultiContextHistory] replace called without contextKey')
      }

      console.log('[MultiContextHistory] replace', { to, data, currentContextKey })
      return baseHistory.replace(to, data)
      // if (isMainContext(contextKey)) {
      //   return baseHistory.replace(to, data)
      // }

      // const ctx = getOrCreateContext(contextKey)
      // const from = ctx.location
      //
      // ctx.stack[ctx.position] = {
      //   location: to,
      //   state: data ?? {},
      // }
      //
      // ctx.location = to
      // ctx.state = data ?? {}
      //
      // notifyListeners(contextKey, to, from, {
      //   type: NavigationType.push,
      //   direction: NavigationDirection.forward,
      //   delta: 0,
      // })
    },

    go(delta: number, triggerListeners = true): void {
      const currentContextKey = getCurrentContextKey()
      if (!currentContextKey) {
        throw new Error('[MultiContextHistory] go called without contextKey')
      }
      console.log('[MultiContextHistory] go', { delta, triggerListeners, currentContextKey })
      baseHistory.go(delta, triggerListeners)
    },

    listen(callback): () => void {
      const currentContextKey = getCurrentContextKey()
      const contextKey = currentContextKey ?? 'main'
      console.log('[MultiContextHistory] listen', { currentContextKey, contextKey })

      return baseHistory.listen(callback)
    },

    createHref(location: HistoryLocation): string {
      return baseHistory.createHref(location)
    },

    destroy(): void {
      baseHistory.destroy()
    },
  }
}
