<script lang="ts">
import { subRouterContextManagerKey } from '@/core/sub-router/injectionSymbols'
import { inject, provide } from 'vue'
import {
  CollectionManager,
  contextStorageCollectionInjectKey,
  contextStorageCollectionItemInjectKey,
  contextStorageHandlersInjectKey,
  useContextStorageCollection,
} from 'vue-context-storage'

function initContextStorage(contextKey: string) {
  const contextStorageCollection = inject(contextStorageCollectionInjectKey)
  if (!contextStorageCollection)
    throw new Error('[DesktopContextInitializer] ContextStorage collection not found')

  function makeContextStorage(collection: CollectionManager, key: string) {
    const item = collection.add({
      key,
    })

    provide(contextStorageCollectionItemInjectKey, item)
    provide(contextStorageHandlersInjectKey, item.handlers)

    item.handlers.forEach((handler) => {
      provide(handler.getInjectionKey(), handler)
    })

    return item
  }

  makeContextStorage(contextStorageCollection, contextKey)
}

export default defineComponent({
  props: {
    defaultKey: {
      type: String,
      default: () => 'main',
    },
  },
  setup(_, { slots }) {
    useContextStorageCollection()

    const subRouterContextManager = inject(subRouterContextManagerKey)
    if (!subRouterContextManager)
      throw new Error('[DesktopContextManager] SubRouterContextManager not found')

    subRouterContextManager.onContextInit((key) => {
      console.log('[DesktopContextManager] Context initialized:', key)
      initContextStorage(key)
    })

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
