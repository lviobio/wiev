import { ComponentPublicInstance, getCurrentInstance } from 'vue'

export function useForwardRef<ExposedMethods extends Record<string, any>>() {
  const currentInstance = getCurrentInstance()
  const elRef = ref<ExposedMethods>()

  function forwardRef(el: Element | ComponentPublicInstance | null) {
    if (currentInstance) {
      currentInstance.exposed = currentInstance.exposeProxy = el
    }

    elRef.value = el as unknown as ExposedMethods
  }

  return [elRef, forwardRef] as const
}
