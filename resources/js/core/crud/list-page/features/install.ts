import type { FeatureContext, ListFeature, ListPageSlot, MergedContributions } from './types'

export interface InstallFeaturesResult<State extends Record<string | symbol, unknown>> {
  state: State
  contributions: MergedContributions
}

/**
 * Sort features by `priority` ascending, install each with the given context,
 * and return the merged state + shallow-merged contributions per render slot.
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
  }

  return { state, contributions }
}
