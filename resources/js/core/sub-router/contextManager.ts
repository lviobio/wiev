import { shallowRef } from 'vue'

type ContextInterface =
  | {
      initialized: false
    }
  | {
      initialized: true
    }

interface ActiveContextInterface {
  key: string
  context: ContextInterface
}

type ContextInitListener = (key: string) => void

type ActiveContextInterfaceRef = ShallowRef<ActiveContextInterface | undefined>

export class SubRouterContextManagerInstance {
  private started = false
  private activeContext: ActiveContextInterfaceRef = shallowRef<ActiveContextInterfaceRef>()
  private activeHistoryContext: ActiveContextInterfaceRef = shallowRef<ActiveContextInterfaceRef>()
  private registered: Map<string, ContextInterface> = new Map()
  private onContextInitListeners: ContextInitListener[] = []

  getActiveContext() {
    return this.activeContext.value
  }

  getActiveHistoryContext() {
    return this.activeHistoryContext.value
  }

  setActive(key: string, history: boolean) {
    const item = this.registered.get(key)

    if (!item) throw new Error(`[SubRouter] Context "${key}" not found`)

    if (!item.initialized) throw new Error(`[SubRouter] Context "${key}" not initialized`)

    let modified = false

    if (this.activeContext.value?.key !== key) {
      this.activeContext.value = {
        key,
        context: item,
      }

      modified = true
    }

    if (history && this.activeHistoryContext.value?.key !== key) {
      this.activeHistoryContext.value = this.activeContext.value
    }

    if (modified) {
      console.log('[SubRouterContextManager] setActive', { key, history })
    }

    return modified
  }

  markAsStarted() {
    this.started = true
  }

  public register(key: string) {
    this.registered.set(key, {
      initialized: false,
    })
  }

  public unregister(key: string) {
    this.registered.delete(key)
  }

  public initialize(key: string) {
    this.registered.get(key)!.initialized = true
    this.onContextInitListeners.forEach((fn) => fn(key))
  }

  onContextInit(fn: ContextInitListener) {
    if (this.started) {
      throw new Error('[SubRouter] adding listener after start is not allowed')
    }

    this.onContextInitListeners.push(fn)
  }
}
