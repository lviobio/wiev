import { watch, type Reactive } from 'vue'
import { ResetPageKey, type FeatureContext, type FiltersFeature } from './types'

export function withFilters<F extends Record<string, unknown>>(
  filters: Reactive<F>,
): FiltersFeature<F> {
  return {
    brand: 'filters',
    install(ctx: FeatureContext) {
      watch(
        filters,
        () => {
          ctx.resolve<() => void>(ResetPageKey)?.()
          ctx.loadDebounced()
        },
        { deep: true },
      )

      return { filters }
    },
  }
}
