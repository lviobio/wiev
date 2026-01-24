<script lang="ts">
import { multiRouterContextManagerKey } from '@/core/multi-router/injectionSymbols'
import { multiRouterContext } from '@/core/multi-router/symbols'
import { viewDepthKey } from 'vue-router'

const MultiRouterContextInner = defineComponent({
  name: 'MultiRouterContextInner',
  props: {
    type: {
      type: String,
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
    location: {
      type: String,
      required: false,
    },
    initialLocation: {
      type: String,
      required: false,
    },
    historyEnabled: {
      type: Boolean,
      default: true,
    },
  },
  setup(props, { slots }) {
    const manager = inject(multiRouterContextManagerKey)!

    console.log('[MultiRouterContext] setup', {
      type: props.type,
      name: props.name,
      location: props.location,
      initialLocation: props.initialLocation,
    })

    if (manager.has(props.name)) {
      console.warn(`[MultiRouterContext] Context "${props.name}" already registered, skipping`)
      return () => slots.default?.()
    }

    manager.register(props.type, props.name, { 
      location: props.location,
      initialLocation: props.initialLocation,
      historyEnabled: props.historyEnabled,
    })

    provide(multiRouterContext, props.name)

    provide(viewDepthKey, ref(0))

    onBeforeUnmount(() => {
      manager.unregister(props.name)
    })

    return () => slots.default?.()
  },
})

export default defineComponent({
  name: 'MultiRouterContext',
  props: {
    type: {
      type: String,
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
    location: {
      type: String,
      required: false,
    },
    initialLocation: {
      type: String,
      required: false,
    },
    /**
     * Whether this context should store its navigation history in the browser history.
     * When false, navigation within this context won't affect browser back/forward.
     * @default true
     */
    historyEnabled: {
      type: Boolean,
      default: true,
    },
  },
  setup(props, { slots }) {
    // Render inner component with key=name+location to force full re-mount when either changes
    // initialLocation is not in key because it's only used as fallback
    return () => h(MultiRouterContextInner, {
      key: `${props.name}:${props.location ?? ''}`,
      type: props.type,
      name: props.name,
      location: props.location,
      initialLocation: props.initialLocation,
      historyEnabled: props.historyEnabled,
    }, slots.default)
  },
})
</script>
