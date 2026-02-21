import type { ContextSyncChannel } from '@/core/list-context/useListContextSync'
import type { FeatureContext, ListFeature, ListPageSlot, MergedContributions } from './types'

export interface InstallFeaturesResult<State extends Record<string | symbol, unknown>> {
  state: State
  contributions: MergedContributions
  contextSync: ContextSyncChannel[]
}

/**
 * Sort features by `priority` ascending, install each with the given context,
 * and return merged state, per-slot contributions, and the aggregated list
 * of context-sync channels in install order.
 */
export function installFeatures<State extends Record<string | symbol, unknown>>(
  features: readonly ListFeature[],
  ctx: FeatureContext,
): InstallFeaturesResult<State> {
  const state = {} as State
  const contributions: MergedContributions = {
    table: {},
    search: {},
    wrapper: {},
  }
  const contextSync: ContextSyncChannel[] = []

  const sorted = [...features].sort((a, b) => a.priority - b.priority)

  for (const feature of sorted) {
    const result = feature.install(ctx)
    Object.assign(state as object, result.state)

    if (result.contributions) {
      for (const slot of Object.keys(result.contributions) as ListPageSlot[]) {
        const slotProps = result.contributions[slot]
        if (slotProps) Object.assign(contributions[slot], slotProps)
      }
    }

    if (result.contextSync?.length) {
      contextSync.push(...result.contextSync)
    }
  }

  return { state, contributions, contextSync }
}
