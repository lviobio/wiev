<script lang="ts">
import { useDesktopContext } from '@/core/navigator/useDesktopContext'

export default defineComponent({
  setup(_, { slots }) {
    const { initialize } = useDesktopContext()

    initialize()

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
