import { usePagination } from '@/core/pagination/base'
import { watchIgnorable } from '@vueuse/core'
import { isEqual } from 'lodash'
import { ResetPageKey, type FeatureContext, type PaginationFeature } from './types'

export function withPagination(): PaginationFeature {
  return {
    brand: 'pagination',
    install(ctx: FeatureContext) {
      const pagination = usePagination()

      ctx.provide(ResetPageKey, () => pagination.resetPage())

      const paginationWatch = watchIgnorable(
        () => pagination.params.value,
        (newVal, oldVal) => {
          if (isEqual(newVal, oldVal)) return

          if (newVal?.per_page !== oldVal?.per_page) {
            paginationWatch.ignoreUpdates(() => {
              pagination.resetPage()
            })
          }

          ctx.loadImmediate()
        },
        { deep: true },
      )

      ctx.onAfterLoad((result) => {
        paginationWatch.ignoreUpdates(() => {
          pagination.applyMeta(result.meta)
        })
      })

      return { pagination }
    },
  }
}
