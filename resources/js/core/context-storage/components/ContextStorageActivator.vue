<script lang="tsx">
import { inject } from 'vue'
import {
  contextStorageCollectionInjectKey,
  contextStorageCollectionItemInjectKey,
} from '@/core/context-storage/injectionSymbols'

export default defineComponent({
  setup(props, { slots }) {
    const collection = inject(contextStorageCollectionInjectKey)!
    const item = inject(contextStorageCollectionItemInjectKey)!

    const onActivate = () => {
      collection.setActive(item)
    }

    return () => <div onMousedown={onActivate}>{slots.default?.()}</div>
  },
})
</script>
