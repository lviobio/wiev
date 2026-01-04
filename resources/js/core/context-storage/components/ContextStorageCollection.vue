<script lang="tsx">
import {
  ContextStorageCollection,
  ContextStorageCollectionItem,
} from '@/core/context-storage/collection'
import { ContextStorageHandlerConstructor } from '@/core/context-storage/handlers'
import { contextStorageCollectionInjectKey } from '@/core/context-storage/injectionSymbols'

export default defineComponent({
  props: {
    handlers: {
      type: Object as PropType<ContextStorageHandlerConstructor[]>,
      required: true,
    },
  },
  setup({ handlers }, { slots }) {
    const lastActive = computed({
      get: () => localStorage.getItem('context-storage-last-active') || 'main',
      set: (value) => localStorage.setItem('context-storage-last-active', value),
    })

    const router = useRouter()

    const isReady = ref(false)

    const initialNavigatorState = new Map<
      ContextStorageHandlerConstructor,
      Record<string, unknown>
    >()
    const initialNavigatorStateResolvers = new Map<
      ContextStorageHandlerConstructor,
      () => Record<string, unknown>
    >()

    handlers.forEach((handler) => {
      if (!handler.getInitialStateResolver) {
        return
      }

      initialNavigatorStateResolvers.set(handler, handler.getInitialStateResolver())
    })

    router.isReady().then(() => {
      initialNavigatorStateResolvers.forEach((resolver, handler) => {
        initialNavigatorState.set(handler, resolver())
      })

      isReady.value = true

      activateLastActiveItem()
    })

    const collection = new ContextStorageCollection(handlers)
    collection.onActiveChange((item) => {
      lastActive.value = item.key
    })

    provide(contextStorageCollectionInjectKey, collection)

    const activateInitialItem = (item: ContextStorageCollectionItem) => {
      item.handlers.forEach((handler) => {
        const state = initialNavigatorState.get(
          handler.constructor as ContextStorageHandlerConstructor,
        )

        if (!state) {
          return
        }

        handler.setInitialState?.(state)
      })

      collection.setActive(item)
    }

    const activateLastActiveItem = () => {
      const lastActiveItem = collection.findItemByKey(lastActive.value)
      if (lastActiveItem) {
        activateInitialItem(lastActiveItem)
        return
      }

      const firstItem = collection.first()
      if (!firstItem) {
        throw new Error('Cannot find first item in collection')
      }

      activateInitialItem(firstItem)
    }

    return () => {
      return slots.default?.()
    }
  },
})
</script>
