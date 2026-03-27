import { usePagination } from '@/core/pagination/base'
import { watchIgnorable, type WatchIgnorableReturn } from '@vueuse/core'
import { isEqual } from 'lodash'
import type { FeatureContext, PaginationFeature } from './types'

export function withPagination(): PaginationFeature {
  return {
    brand: 'pagination',
    install(ctx: FeatureContext) {
      const pagination = usePagination()
      let paginationWatch: WatchIgnorableReturn | undefined

      ctx.onAfterLoad((result) => {
        paginationWatch?.ignoreUpdates(() => {
          pagination.applyMeta(result.meta)
        })
      })

      ctx.onEnableWatchers(() => {
        paginationWatch = watchIgnorable(
          () => pagination.params.value,
          (newVal, oldVal) => {
            if (isEqual(newVal, oldVal)) return

            if (newVal?.per_page !== oldVal?.per_page) {
              paginationWatch?.ignoreUpdates(() => {
                pagination.resetPage()
              })
            }

            ctx.loadImmediate()
          },
          { deep: true },
        )
      })

      return { pagination }
    },
  }
}
