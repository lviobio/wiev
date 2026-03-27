import { ref, type Ref, watch } from 'vue'
import type { FeatureContext, SearchFeature } from './types'

export function withSearch(): SearchFeature {
  return {
    brand: 'search',
    install(ctx: FeatureContext) {
      const search = ref<string | undefined>('') as Ref<string | undefined>

      ctx.onEnableWatchers(() => {
        watch(search, (newVal, oldVal) => {
          if (newVal === oldVal) return

          ctx.resetPage()
          ctx.loadDebounced()
        })
      })

      return { search }
    },
  }
}
