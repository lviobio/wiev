import { SubRouterContextManagerInstance } from '@/core/sub-router/contextManager'
import { subRouterContextManager } from '@/core/sub-router/symbols'

export const subRouterContextManagerKey: InjectionKey<SubRouterContextManagerInstance> =
  subRouterContextManager
