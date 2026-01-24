import { 
  multiRouterContextKey, 
  multiRouterContextManagerKey 
} from '@/core/multi-router/injectionSymbols'
import type { Router } from 'vue-router'

export function useMultiRouterContext() {
  const manager = inject(multiRouterContextManagerKey)
  const contextKey = inject(multiRouterContextKey)
  
  if (!manager) {
    throw new Error('[useMultiRouterContext] Must be used within MultiRouterContextProvider')
  }
  
  if (!contextKey) {
    throw new Error('[useMultiRouterContext] Must be used within MultiRouterContext')
  }

  const router = computed<Router>(() => manager.getRouter(contextKey))
  
  const route = computed(() => router.value.currentRoute.value)
  
  const isActive = computed(() => manager.getActiveContextRef().value?.key === contextKey)
  
  const isHistoryActive = computed(() => manager.getActiveHistoryContextRef().value?.key === contextKey)
  
  const historyEnabled = computed(() => manager.getContextHistoryEnabled(contextKey))
  
  const activate = (updateHistory = true) => {
    manager.setActive(contextKey, updateHistory)
  }

  return { 
    manager,
    contextKey,
    router,
    route,
    isActive,
    isHistoryActive,
    historyEnabled,
    activate,
  }
}