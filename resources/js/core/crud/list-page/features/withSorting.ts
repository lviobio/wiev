import { useSorting, type UseSortingOptions } from '@/core/sorting/base'
import { isEqual } from 'lodash'
import { watch } from 'vue'
import { PaginationResetPageKey, type FeatureContext, type SortingFeature } from './types'

export function withSorting(options?: UseSortingOptions): SortingFeature {
  return {
    brand: 'sorting',
    priority: 2000,
    install(ctx: FeatureContext) {
      const sorting = useSorting(options)

      watch(
        () => sorting.state.value,
        (newVal, oldVal) => {
          if (isEqual(newVal, oldVal)) return

          ctx.resolve<() => void>(PaginationResetPageKey)?.()
          ctx.loadImmediate()
        },
      )

      return { state: { sorting } }
    },
  }
}
