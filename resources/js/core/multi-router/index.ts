import { MultiRouterManagerInstance } from '@/core/multi-router/contextManager'
import { multiRouterContextManagerKey } from '@/core/multi-router/injectionSymbols'
import { multiRouterContext } from '@/core/multi-router/symbols'
import { getCurrentInstance as _getCurrentInstance, App, ComponentInternalInstance } from 'vue'
import {
  createRouter,
  routeLocationKey,
  Router,
  RouterHistory,
  routerKey,
  RouterLink,
  RouterOptions,
  RouterView,
  routerViewLocationKey,
} from 'vue-router'

const getCurrentInstance = () =>
  _getCurrentInstance() as ComponentInternalInstance & {
    provides: any
  }

function installContextAwareRouterResolvers(app: App, contextManager: MultiRouterManagerInstance) {
  if (app._context.provides[routerKey]) {
    throw new Error('Router installed to app, this may cause unexpected behavior')
  }

  function isVueDevtoolsCall(): boolean {
    return !!new Error().stack?.includes('chrome-extension://nhdogjmejiglipccpnnnanhbledajbpd')
  }

  function getInstanceContextKey(): string | null {
    const instance = getCurrentInstance()

    if (!instance) {
      if (isVueDevtoolsCall()) return null
      throw new Error('No instance found')
    }

    const contextKey = instance.provides[multiRouterContext]
    if (!contextKey) {
      throw new Error('Context key not found')
    }

    return contextKey
  }

  const routerProperty = {
    enumerable: true,
    get() {
      const contextKey = getInstanceContextKey()
      if (!contextKey) return null

      if (!contextManager.has(contextKey)) {
        if (isVueDevtoolsCall()) return null
        throw new Error(`Router not found for context ${contextKey}`)
      }

      return contextManager.getRouter(contextKey)
    },
  }

  const routeProperty = {
    enumerable: true,
    get() {
      const contextKey = getInstanceContextKey()
      if (!contextKey || !contextManager.has(contextKey)) return null

      return contextManager.getRouter(contextKey).currentRoute.value
    },
  }

  Object.defineProperty(app._context.provides, routerKey, routerProperty)
  Object.defineProperty(app.config.globalProperties, '$router', routerProperty)

  Object.defineProperty(app._context.provides, routeLocationKey, routeProperty)
  Object.defineProperty(app.config.globalProperties, '$route', routeProperty)

  Object.defineProperty(app._context.provides, routerViewLocationKey, {
    enumerable: true,
    configurable: true,
    get() {
      const contextKey = getInstanceContextKey()
      if (!contextKey || !contextManager.has(contextKey)) return null

      return contextManager.getRouter(contextKey).currentRoute
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

function installComponents(app: App) {
  app.component('RouterLink', RouterLink)
  app.component('RouterView', RouterView)
}

type MultiRouterInterface = {
  getByContextName: (name: string) => Router
  install: (app: App) => void
}

interface ContextTypeOptions {
  canUseAsHistoryContext: boolean
  single: boolean
}

export type ContextTypes = Record<string, ContextTypeOptions>

export const contextTemplateWindows = {
  window: { canUseAsHistoryContext: true, single: false },
} satisfies ContextTypes

export const contextTemplateTabs = {
  tab: { canUseAsHistoryContext: true, single: false },
} satisfies ContextTypes

export const contextTemplateMainWithWindows = {
  main: { canUseAsHistoryContext: true, single: true },
  ...contextTemplateWindows,
} satisfies ContextTypes
export const contextTemplateDesktopWithWindows = {
  desktop: { canUseAsHistoryContext: false, single: true },
  ...contextTemplateWindows,
} satisfies ContextTypes
export const contextTemplateTabsWithWindows = {
  ...contextTemplateTabs,
  ...contextTemplateWindows,
} satisfies ContextTypes

type CustomRouterOptions = Omit<RouterOptions, 'history'> & {
  history: () => RouterHistory
  types: ContextTypes
}

export function createMultiRouter(options: CustomRouterOptions): MultiRouterInterface {
  let contextManager: MultiRouterManagerInstance

  return {
    getByContextName: (name: string) => {
      return contextManager.getRouter(name)
    },
    install: (app: App) => {
      const makeRouter = (contextKey: string, history: RouterHistory): Router => {
        const router = createRouter({
          ...options,
          history,
        })

        Object.assign(router, { contextKey })

        return router
      }

      contextManager = new MultiRouterManagerInstance(
        app,
        options.types,
        options.history,
        makeRouter,
      )
      app.provide(multiRouterContextManagerKey, contextManager)

      installComponents(app)
      installContextAwareRouterResolvers(app, contextManager)
    },
  }
}
