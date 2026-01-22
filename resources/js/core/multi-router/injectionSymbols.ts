import { MultiRouterManagerInstance } from '@/core/multi-router/contextManager'
import { multiRouterContext, multiRouterContextManager } from '@/core/multi-router/symbols'

export const multiRouterContextManagerKey: InjectionKey<MultiRouterManagerInstance> =
  multiRouterContextManager

export const multiRouterContextKey: InjectionKey<string> = multiRouterContext
