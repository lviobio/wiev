import { usePagination } from '@/core/pagination/base'
import { watchIgnorable } from '@vueuse/core'
import { isEqual } from 'lodash'
import { PaginationResetPageKey, type FeatureContext, type PaginationFeature } from './types'

export function withPagination(): PaginationFeature {
  return {
    brand: 'pagination',
    priority: 1000,
    install(ctx: FeatureContext) {
      const pagination = usePagination()

      ctx.provide(PaginationResetPageKey, () => pagination.resetPage())

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
          //TODO: Create schema for page & cursor pagination and validate result.meta before applying
          pagination.applyMeta(result.meta)
        })
      })

      return { state: { pagination } }
    },
  }
}
