<script lang="ts">
import { desktopContextKey } from '@/core/injectionSymbols'
import { subRouterContextManagerKey } from '@/core/sub-router/injectionSymbols'
import { inject } from 'vue'

export default defineComponent({
  setup(_, { slots }) {
    const subRouterContextManager = inject(subRouterContextManagerKey)
    if (!subRouterContextManager)
      throw new Error('[DesktopContext] - SubRouterContextManager not found')
    const contextKey = inject(desktopContextKey)!

    subRouterContextManager.initialize(contextKey)

    console.log(
      '[DesktopContextInitializer] initialized (sub-router & vue-context-storage)',
      contextKey,
    )

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
