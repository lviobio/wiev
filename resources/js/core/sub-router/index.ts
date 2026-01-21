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
  RouterLink,
  RouterOptions,
  RouterView,
  routerViewLocationKey,
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

function installContextAwareRouterResolvers(app: App) {
  if (app._context.provides[routerKey]) {
    throw new Error('Router installed to app, this may cause unexpected behavior')
  }

  const routersMap = (app._context.provides['sub-routers'] = new Map<string, Router>())

  function getInstanceContextKey() {
    const instance = getCurrentInstance()

    if (!instance) {
      const stack = new Error().stack
      if (stack && stack.includes('chrome-extension://nhdogjmejiglipccpnnnanhbledajbpd')) {
        // console.log('vue ext called!', stack.split('\n'))
        return null
      }
      throw new Error('No instance found')
    }

    const contextKey = instance.provides[desktopContextKey]

    if (!contextKey) {
      throw new Error('Context key not found')
    }

    return contextKey
  }

  const routerProperty = {
    enumerable: true,
    get() {
      const contextKey = getInstanceContextKey()

      if (!routersMap.has(contextKey)) {
        const stack = new Error().stack
        if (stack && stack.includes('chrome-extension://nhdogjmejiglipccpnnnanhbledajbpd')) {
          // console.log('vue ext called!', stack.split('\n'))
          return null
        }
        throw new Error(`Router not found for context ${contextKey}`)
      }

      return routersMap.get(contextKey)!
    },
  }

  const routeProperty = {
    enumerable: true,
    get() {
      const contextKey = getInstanceContextKey()

      if (!routersMap.has(contextKey)) {
        throw new Error(`Router not found for context ${contextKey}`)
      }

      return routersMap.get(contextKey)!.currentRoute.value
    },
  }

  Object.defineProperty(app._context.provides, routerKey, routerProperty)
  Object.defineProperty(app.config.globalProperties, '$router', routerProperty)

  Object.defineProperty(app._context.provides, routeLocationKey, routeProperty)
  Object.defineProperty(app.config.globalProperties, '$route', routeProperty)

  Object.defineProperty(app._context.provides, routerViewLocationKey, {
    enumerable: true,
    // writable: true,
    configurable: true,
    get() {
      const contextKey = getInstanceContextKey()

      if (!routersMap.has(contextKey)) {
        throw new Error(`Router not found for context ${contextKey}`)
      }

      return routersMap.get(contextKey)!.currentRoute
    },
    /**
     * Called by RouterView component
     */
    set(value) {
      const instance = getCurrentInstance()

      const originalPrototype = Object.getPrototypeOf(instance.provides)
      const originalProperties = Object.getOwnPropertyDescriptors(instance.provides)

      const { [routerViewLocationKey]: _, ...filteredProperties } = originalProperties

      instance.provides = Object.defineProperties(
        Object.create(originalPrototype),
        filteredProperties,
      )

      Object.defineProperty(instance.provides, routerViewLocationKey, {
        value,
        writable: true,
        enumerable: true,
        configurable: true,
      })
    },
  })
}

// function installRouterCallWithContextKey(router: Router, data: SubRouterData) {
//   router.
// }

function installComponents(app: App) {
  app.component('RouterLink', RouterLink)
  app.component('RouterView', RouterView)
}

function installContextManager(app: App, contextManager: SubRouterContextManagerInstance) {
  app.provide(subRouterContextManagerKey, contextManager)
}

function initSubRouter(app: App, options: CustomRouterOptions) {
  const makeRouter = (contextKey: string) => {
    const history = createHistoryWrapper(options.history, contextKey)
    const router = createRouter({
      ...options,
      history,
    })

    decorateRouterWithContextCalls(router)

    Object.assign(router, {
      contextKey,
    })

    return { router, history }
  }

  const contextManager = new SubRouterContextManagerInstance(app, makeRouter)

  // installModifiedRouterInGlobalProperties(app)
  installComponents(app)
  installContextAwareRouterResolvers(app)
  // installRouterCallWithContextKey(router, data)
  // decorateRouterWithContextCalls(router)
  installContextManager(app, contextManager)
}

type CustomRouterOptions = Omit<RouterOptions, 'history'> & { history: () => RouterHistory }
export function createSubRouter(options: CustomRouterOptions) {
  // const router = createRouter({
  //   ...options,
  //   history: createHistoryWrapper(options.history),
  // })
  // app.provide(subRouterContextManagerKey, contextManager)

  // const { install } = router
  // decorateRouterWithContextCalls(router)

  // Object.assign(router, {
  //   install: (app: App) => {
  //     // install(app)
  //
  //     initSubRouter(app, router)
  //   },
  // })

  return {
    install: (app: App) => {
      // install(app)

      initSubRouter(app, options)
    },
  }
}
