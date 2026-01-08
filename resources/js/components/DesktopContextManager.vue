<script lang="ts">
import { desktopContextManager } from '@/core/injectionSymbols'
import { inject } from 'vue'
import { contextStorageCollectionInjectKey } from 'vue-context-storage'

export default defineComponent({
  props: {
    defaultKey: {
      type: String,
      default: () => 'main',
    },
  },
  setup(props, { slots }) {
    const contextStorageCollection = inject(contextStorageCollectionInjectKey)
    if (!contextStorageCollection)
      throw new Error('[ContextStorage] Context storage collection not found')

    const manager = inject(desktopContextManager)
    if (!manager) throw new Error('[DesktopContextManager] Manager not found')

    manager.setContextStorageCollection(contextStorageCollection)
    manager.active.value = props.defaultKey

    return () => slots.default?.()
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
