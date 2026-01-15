import { desktopContextKey } from '@/core/injectionSymbols'
import { SubRouterContextManagerInstance } from '@/core/sub-router/contextManager'
import { createHistoryWrapper } from '@/core/sub-router/history-wrapper'
import { subRouterContextManagerKey } from '@/core/sub-router/injectionSymbols'
import { contextKeySymbol } from '@/core/sub-router/symbols'
import {
  getCurrentInstance as _getCurrentInstance,
  App,
  ComponentCustomProperties,
  ComponentInternalInstance,
} from 'vue'
import {
  createRouter,
  RouteLocationAsPathGeneric,
  RouteLocationAsRelativeGeneric,
  routeLocationKey,
  RouteLocationRaw,
  Router,
  RouterHistory,
  routerKey,
  RouterOptions,
} from 'vue-router'

interface SubRouterData {
  currentContextKey: string | undefined
  previousContextKey: string | undefined
}
const callWithSubRouterContextKeyData: SubRouterData = {
  currentContextKey: undefined,
  previousContextKey: undefined,
}
const callWithSubRouterContextKey = <T>(contextKey?: string, fn: () => T): T => {
  const prev = callWithSubRouterContextKeyData.currentContextKey
  callWithSubRouterContextKeyData.currentContextKey = contextKey
  try {
    return fn()
  } finally {
    callWithSubRouterContextKeyData.currentContextKey = prev
  }
}

const getCurrentInstance = () =>
  _getCurrentInstance() as ComponentInternalInstance & {
    provides: any
  }

// function installAppProvidesRouterWithDecoratedContextCalls(app: App) {
//   // app._context.provides[routerKey] = app._context.provides[routerKey]
// }

export function getCurrentContextKey() {
  return callWithSubRouterContextKeyData.currentContextKey
}

function decorateRouterWithContextCalls(router: Router) {
  /**
   * Router's push / replace are called in .then of navigate() promise, so this is hack to preserve contextKey
   */
  function withContextKeyInState(
    to: RouteLocationRaw,
    contextKey?: string,
  ): RouteLocationAsRelativeGeneric | RouteLocationAsPathGeneric {
    if (typeof to === 'string') {
      to = { path: to, state: { [contextKeySymbol]: contextKey } }
    }

    to = { ...to }

    if (to.state) {
      Object.assign(to.state, { [contextKeySymbol]: contextKey })
    } else {
      to.state = { [contextKeySymbol]: contextKey }
    }

    return to
  }

  const { push, replace, back, forward, go, beforeEach, beforeResolve, afterEach, onError } = router
  Object.assign(router, {
    contextKey: undefined,
    getContextKey() {
      if (!this.contextKey) {
        debugger
      }
      return this.contextKey
    },
    push(to) {
      to = withContextKeyInState(to, this.getContextKey())

      return callWithSubRouterContextKey(this.getContextKey(), () => push(to))
    },
    replace(to) {
      to = withContextKeyInState(to, this.getContextKey())

      return callWithSubRouterContextKey(this.getContextKey(), () => replace(to))
    },
    back() {
      return callWithSubRouterContextKey(this.getContextKey(), back)
    },
    forward() {
      return callWithSubRouterContextKey(this.getContextKey(), forward)
    },
    go(delta) {
      return callWithSubRouterContextKey(this.getContextKey(), () => go(delta))
    },
    beforeEach(guard) {
      return callWithSubRouterContextKey(this.getContextKey(), () => {
        return beforeEach((...args) =>
          callWithSubRouterContextKey(this.getContextKey(), () => {
            guard(...args) // .bind(undefined)
          }),
        )
      })
    },
    beforeResolve(guard) {
      return callWithSubRouterContextKey(this.getContextKey(), () =>
        beforeResolve((...args) =>
          callWithSubRouterContextKey(this.getContextKey(), () => guard(...args)),
        ),
      )
    },
    afterEach(guard) {
      return callWithSubRouterContextKey(this.getContextKey(), () =>
        afterEach((...args) =>
          callWithSubRouterContextKey(this.getContextKey(), () => guard(...args)),
        ),
      )
    },
    onError(handler) {
      return callWithSubRouterContextKey(this.getContextKey(), () =>
        onError((...args) =>
          callWithSubRouterContextKey(this.getContextKey(), () => handler(...args)),
        ),
      )
    },
  } satisfies Partial<Router> & {
    contextKey: string | undefined
    getContextKey(): string | undefined
  })
}

function installModifiedRouterInGlobalProperties(app: App) {
  delete app._context.provides[routerKey]

  const originalRouter = app.config.globalProperties.$router
  const originalRoute = app.config.globalProperties.$route
  const originalProperties = Object.getOwnPropertyDescriptors(app.config.globalProperties)

  const routerProperty = {
    enumerable: true,
    get() {
      const instance = getCurrentInstance()

      if (instance) {
        //todo: desktopContextKey заменить на кастомный, внутри sub-router папки
        return { ...originalRouter, contextKey: instance.provides[desktopContextKey] }
      }

      return originalRouter
    },
  }

  Object.defineProperty(app._context.provides, routerKey, routerProperty)

  if (!Object.keys(originalProperties).includes('$router')) {
    throw new Error('Global property $router not found')
  }
  if (!Object.keys(originalProperties).includes('$route')) {
    throw new Error('Global property $route not found')
  }

  const { $router: _, $route: __, ...filteredProperties } = originalProperties

  app.config.globalProperties = Object.defineProperties(
    {},
    filteredProperties,
  ) as ComponentCustomProperties

  Object.defineProperty(app.config.globalProperties, '$router', routerProperty)
  Object.defineProperty(app.config.globalProperties, '$route', {
    enumerable: true,
    get: () => {
      const instance = getCurrentInstance()
      return instance?.provides[routeLocationKey] ?? originalRoute
    },
  })

  return {
    router: originalRouter,
  }
}

// function installRouterCallWithContextKey(router: Router, data: SubRouterData) {
//   router.
// }

function installContextManager(
  app: App,
  router: Router,
  contextManager: SubRouterContextManagerInstance,
) {
  app.provide(subRouterContextManagerKey, contextManager)
  router.isReady().then(() => {
    contextManager.markAsStarted()
  })
}

function initSubRouter(app: App, router: Router) {
  const contextManager = new SubRouterContextManagerInstance()

  installModifiedRouterInGlobalProperties(app)
  // installRouterCallWithContextKey(router, data)
  // decorateRouterWithContextCalls(router)
  installContextManager(app, router, contextManager)
}

export function createSubRouter(
  options: Omit<RouterOptions, 'history'> & { history: () => RouterHistory },
) {
  const router = createRouter({
    ...options,
    history: createHistoryWrapper(options.history),
  })

  const { install } = router
  decorateRouterWithContextCalls(router)

  Object.assign(router, {
    install: (app: App) => {
      install(app)

      initSubRouter(app, router)
    },
  })

  return router
}
