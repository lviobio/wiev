import { inject, InjectionKey } from 'vue'
import { Router, useRouter } from 'vue-router'

export const OriginalRouterSymbol: InjectionKey<Router> = Symbol('original-router')
export const OriginalRouteSymbol: InjectionKey<Router> = Symbol('original-route')

export function getOriginalRouter(): Router {
  const result = inject(OriginalRouterSymbol, () => useRouter(), true)

  provide(OriginalRouterSymbol, result)

  return result
}
