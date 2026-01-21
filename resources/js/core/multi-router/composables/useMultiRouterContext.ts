import { multiRouterContextManagerKey } from '@/core/multi-router/injectionSymbols'

export function useMultiRouterContext() {
  const manager = inject(multiRouterContextManagerKey)!

  return { manager }
}