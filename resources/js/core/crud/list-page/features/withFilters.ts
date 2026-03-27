import { type Reactive, watch } from 'vue'
import type { FeatureContext, FiltersFeature } from './types'

export function withFilters<F extends Record<string, unknown>>(
  filters: Reactive<F>,
): FiltersFeature<F> {
  return {
    brand: 'filters',
    install(ctx: FeatureContext) {
      ctx.onEnableWatchers(() => {
        watch(
          filters,
          () => {
            ctx.resetPage()
            ctx.loadDebounced()
          },
          { deep: true },
        )
      })

      return { filters }
    },
  }
}
