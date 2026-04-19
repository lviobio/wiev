import { createSearchSyncChannel } from '@/core/list-context/channels'
import { ref, watch, type Ref } from 'vue'
import { PaginationResetPageKey, type FeatureContext, type SearchFeature } from './types'

export function withSearch(): SearchFeature {
  return {
    brand: 'search',
    priority: 3000,
    install(ctx: FeatureContext) {
      const search = ref<string | undefined>(undefined) as Ref<string | undefined>

      watch(search, (newVal, oldVal) => {
        if (newVal === oldVal) return

        ctx.resolve<() => void>(PaginationResetPageKey)?.()
        ctx.loadDebounced()
      })

      return {
        state: { search },
        contextSync: [createSearchSyncChannel(search)],
      }
    },
  }
}
