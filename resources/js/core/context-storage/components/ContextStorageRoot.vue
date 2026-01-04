<template>
  <ContextStorageCollection :handlers="handlers">
    <ContextStorageProvider item-key="main">
      <ContextStorageActivator>
        <slot />
      </ContextStorageActivator>
    </ContextStorageProvider>
  </ContextStorageCollection>
</template>

<script setup lang="ts">
import ContextStorageActivator from '@/core/context-storage/components/ContextStorageActivator.vue'
import ContextStorageCollection from '@/core/context-storage/components/ContextStorageCollection.vue'
import ContextStorageProvider from '@/core/context-storage/components/ContextStorageProvider.vue'
import { ContextStorageHandlerConstructor } from '@/core/context-storage/handlers'
import { ContextStorageQueryHandler } from '@/core/context-storage/handlers/query'

interface Props {
  handlers: ContextStorageHandlerConstructor[]
}

const { handlers = [ContextStorageQueryHandler] } = defineProps<Props>()
</script>
