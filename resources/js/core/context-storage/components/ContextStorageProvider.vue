<script lang="tsx">
import {
  contextStorageCollectionInjectKey,
  contextStorageCollectionItemInjectKey,
  contextStorageHandlersInjectKey,
} from '@/core/context-storage/injectionSymbols'
import { inject } from 'vue'

export default defineComponent({
  props: {
    itemKey: {
      type: String,
      required: true,
    },
  },
  setup(props, { slots }) {
    const collection = inject(contextStorageCollectionInjectKey)
    if (!collection) throw new Error('Context storage collection not found')

    const item = collection.add({
      key: props.itemKey,
    })

    provide(contextStorageCollectionItemInjectKey, item)
    provide(contextStorageHandlersInjectKey, item.handlers)

    item.handlers.forEach((handler) => {
      provide(handler.getInjectionKey(), handler)
    })

    onUnmounted(() => {
      collection.remove(item)
    })

    return () => slots.default?.()
  },
})
</script>
