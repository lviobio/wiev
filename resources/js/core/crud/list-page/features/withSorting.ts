import { useSorting, type UseSortingOptions } from '@/core/sorting/base'
import { isEqual } from 'lodash'
import { watch } from 'vue'
import type { FeatureContext, SortingFeature } from './types'

export function withSorting(options?: UseSortingOptions): SortingFeature {
  return {
    brand: 'sorting',
    install(ctx: FeatureContext) {
      const sorting = useSorting(options)

      ctx.onEnableWatchers(() => {
        watch(
          () => sorting.state.value,
          (newVal, oldVal) => {
            if (isEqual(newVal, oldVal)) return

            ctx.resetPage()
            ctx.loadImmediate()
          },
        )
      })

      return { sorting }
    },
  }
}
