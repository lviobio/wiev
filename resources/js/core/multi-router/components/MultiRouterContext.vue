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
    }, slots.default)
  },
})
</script>

<!--<script lang="ts">-->
<!--import { desktopFocusManager } from '@/core/injectionSymbols'-->
<!--import { multiContextActiveRef } from '@/router'-->
<!--import { readonly } from 'vue'-->
<!--import { contextStorageCollectionInjectKey } from 'vue-context-storage'-->

<!--export interface DesktopFocusManagerInterface {-->
<!--  active: Ref<string>-->
<!--  setActive: (key: string) => void-->
<!--}-->

<!--export default defineComponent({-->
<!--  setup(_, { slots }) {-->
<!--    const contextStorageCollection = inject(contextStorageCollectionInjectKey)!-->

<!--    const manager: DesktopFocusManagerInterface = {-->
<!--      active: readonly(multiContextActiveRef),-->
<!--      setActive: (key: string) => {-->
<!--        multiContextActiveRef.value = key-->
<!--      },-->
<!--    }-->

<!--    provide(desktopFocusManager, manager)-->

<!--    contextStorageCollection.onActiveChange((item) => {-->
<!--      manager.setActive(item.key)-->
<!--    })-->

<!--    return () => slots.default?.()-->
<!--  },-->
<!--})-->
<!--</script>-->
