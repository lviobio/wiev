import { injectLocal, provideLocal } from '@vueuse/core'
import type { DefineComponent } from 'vue'
import { defineComponent } from 'vue'

type ProviderType<T> = DefineComponent<{}>
export type ReturnContextType<T> = {
  readonly key: InjectionKey<T>
  get: () => T
  init: () => void
  Provider: ProviderType<T>
}

export function createContext<T>(
  key: InjectionKey<T>,
  defaultFactory: () => T,
): ReturnContextType<T> {
  const get = () => {
    return injectLocal<T>(
      key,
      () => {
        console.warn('[createContext] parent context not found, using default factory')

        return defaultFactory()
      },
      true,
    )
  }

  const init = () => {
    const defaultValue = defaultFactory()

    provideLocal(key, defaultValue)
  }

  const Provider: ProviderType<T> = defineComponent({
    name: 'ContextProvider',
    setup(props, { slots }) {
      init()
      return () => slots.default?.()
    },
  })

  return { key, get, init, Provider }
}
