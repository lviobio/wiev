import { desktopContextKey, desktopContextManager } from '@/core/injectionSymbols'
import { App, ComponentCustomProperties, Plugin, provide } from 'vue'
import {
  CollectionManager,
  CollectionManagerItem,
  contextStorageCollectionItemInjectKey,
  contextStorageHandlersInjectKey,
} from 'vue-context-storage'
import { routeLocationKey, type Router, routerKey } from 'vue-router'

function makeContextStorage(collection: CollectionManager, key: string) {
  const item = collection.add({
    key,
  })

  provide(contextStorageCollectionItemInjectKey, item)
  provide(contextStorageHandlersInjectKey, item.handlers)

  item.handlers.forEach((handler) => {
    provide(handler.getInjectionKey(), handler)
  })

  return item
}

type ContextInterface =
  | {
      initialized: false
    }
  | {
      initialized: true
      contextStorageItem: CollectionManagerItem
    }

interface ActiveContextInterface {
  key: string
  context: ContextInterface
}

type ActiveContextInterfaceRef = ShallowRef<ActiveContextInterface | undefined>

export interface DesktopContextManagerInterface {
  active: ActiveContextInterfaceRef
  setActive: (key: string) => void

  register(key: string): void
  unregister(key: string): void
  initialize(key: string): void
  routerPlugin(router: Router): Plugin
}

export class DesktopContextManagerInstance implements DesktopContextManagerInterface {
  active: ActiveContextInterfaceRef
  private registered: Map<string, ContextInterface>
  private contextStorageCollection!: CollectionManager

  constructor() {
    this.active = shallowRef<ActiveContextInterfaceRef>(undefined)
    this.registered = new Map()
  }

  public setContextStorageCollection(collection: CollectionManager) {
    this.contextStorageCollection = collection
  }

  public setActive(key: string) {
    const item = this.registered.get(key)
    if (!item) throw new Error(`[DesktopContextManager] Context "${key}" not found`)

    if (!item.initialized)
      throw new Error(`[DesktopContextManager] Context "${key}" not initialized`)

    if (this.active.value?.key === key) {
      return
    }

    console.log('[ContextManager] setActive:', key)
    this.active.value = {
      key,
      context: item,
    }

    this.contextStorageCollection.setActive(item.contextStorageItem)
  }

  public register(key: string) {
    console.log('[ContextManager] register', key)
    if (this.registered.has(key))
      throw new Error(`[DesktopContextManager] Context "${key}" already registered`)

    this.registered.set(key, {
      initialized: false,
    })

    provide(desktopContextKey, key)
  }

  public initialize(key: string) {
    console.log('[ContextManager] initialize', key)
    const item = this.registered.get(key)!
    if (!item) throw new Error(`[DesktopContextManager] Context "${key}" not found`)
    if (item.initialized) {
      console.warn(`[DesktopContextManager] Context "${key}" already initialized`)
      return
    }
    Object.assign(item, {
      initialized: true,
      contextStorageItem: makeContextStorage(this.contextStorageCollection, key),
    })
  }

  public unregister(key: string) {
    console.log('[ContextManager] unregister', key)
    const item = this.registered.get(key)!

    if (item.initialized) {
      this.contextStorageCollection.remove(item.contextStorageItem)
    }

    this.registered.delete(key)

    this.activateLast()
  }

  private activateLast() {
    const last = [...this.registered.keys()].at(-1)

    if (last) {
      this.setActive(last)
    } else {
      this.active.value = undefined
    }
  }

  public install(app: App, router: Router) {
    app.provide(desktopContextManager, this)
    // app.config.globalProperties.$router = router

    const originalProperties = Object.getOwnPropertyDescriptors(app.config.globalProperties)

    if (!Object.keys(originalProperties).includes('$router')) {
      throw new Error('Global property $router not found')
    }
    if (!Object.keys(originalProperties).includes('$route')) {
      throw new Error('Global property $route not found')
    }

    const originalRouter = app.config.globalProperties.$router
    const originalRoute = app.config.globalProperties.$route

    const { $router: _, $route: __, ...filteredProperties } = originalProperties

    app.config.globalProperties = Object.defineProperties(
      {},
      filteredProperties,
    ) as ComponentCustomProperties

    Object.defineProperty(app.config.globalProperties, '$router', {
      enumerable: true,
      get: () => {
        const instance = getCurrentInstance()
        return instance?.provides[routerKey] ?? originalRouter
      },
    })
    Object.defineProperty(app.config.globalProperties, '$route', {
      enumerable: true,
      get: () => {
        const instance = getCurrentInstance()
        // console.log('got $route')
        return instance?.provides[routeLocationKey] ?? originalRoute
      },
    })
  }

  routerPlugin(router: Router): Plugin {
    return {
      install: (app) => {
        app.use(router)
        this.install(app, router)
      },
    }
  }
}
