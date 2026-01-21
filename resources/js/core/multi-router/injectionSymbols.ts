import { MultiRouterManagerInstance } from '@/core/multi-router/contextManager'
import { multiRouterContextManager } from '@/core/multi-router/symbols'

export const multiRouterContextManagerKey: InjectionKey<MultiRouterManagerInstance> =
  multiRouterContextManager
