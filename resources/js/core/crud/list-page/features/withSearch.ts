import { ref, watch, type Ref } from 'vue'
import { ResetPageKey, type FeatureContext, type SearchFeature } from './types'

export function withSearch(): SearchFeature {
  return {
    brand: 'search',
    install(ctx: FeatureContext) {
      const search = ref<string | undefined>('') as Ref<string | undefined>

      watch(search, (newVal, oldVal) => {
        if (newVal === oldVal) return

        ctx.resolve<() => void>(ResetPageKey)?.()
        ctx.loadDebounced()
      })

      return { search }
    },
  }
}
