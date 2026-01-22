<script lang="ts">
import {
  multiRouterContextKey,
  multiRouterContextManagerKey,
} from '@/core/multi-router/injectionSymbols'
import { inject } from 'vue'

export default defineComponent({
  setup(_, { slots }) {
    const contextKey = inject(multiRouterContextKey)!
    const manager = inject(multiRouterContextManagerKey)!

    const onActivate = () => {
      if (manager.setActive(contextKey, true)) {
        console.log('[MultiRouterContextActivator] activated', contextKey)
      }
    }

    return () => h('div', { onMousedown: onActivate }, slots.default?.())
  },
})
</script>
