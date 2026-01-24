<script lang="ts">
import {
  multiRouterContextKey,
  multiRouterContextManagerKey,
} from '@/core/multi-router/injectionSymbols'

export default defineComponent({
  props: {
    as: {
      type: String as PropType<string | null>,
      default: null,
    },
  },
  setup(props, { slots }) {
    const contextKey = inject(multiRouterContextKey)
    const manager = inject(multiRouterContextManagerKey)

    if (!contextKey || !manager) {
      console.warn('[MultiRouterContextActivator] Must be used within MultiRouterContext')
      return () => slots.default?.()
    }

    const onActivate = (e: MouseEvent) => {
      e.stopPropagation()
      if (manager.setActive(contextKey, true)) {
        console.log('[MultiRouterContextActivator] activated', contextKey)
      }
    }

    return () => {
      const children = slots.default?.()
      
      if (!props.as) {
        // Fragment mode - attach event to first child or wrap in span
        if (children && children.length === 1) {
          return h(children[0], { onMousedown: onActivate })
        }
        // Multiple children - wrap in span
        return h('span', { onMousedown: onActivate }, children)
      }
      
      // Custom element mode
      return h(props.as, { onMousedown: onActivate }, children)
    }
  },
})
</script>
