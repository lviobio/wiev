<template>
  <CustomSuspense ref="suspenseRef" :validate="(err: Error) => err instanceof SpecificException">
    <template #loading>
      <div class="absolute top-0 right-0 bottom-0 left-0 z-30 flex items-center justify-center">
        <NSpin size="large" />
      </div>
    </template>

    <template #error="{ error }">
      <AppSuspenseErrorView :error="error" @retry="suspenseRef?.retry()" />
    </template>

    <slot />
  </CustomSuspense>
</template>

<script setup lang="ts">
import { CustomSuspenseInst } from '@/components/CustomSuspense.vue'
import { SpecificException } from '@/core/errors'
import { useTemplateRef } from 'vue'

const suspenseRef = useTemplateRef<CustomSuspenseInst>('suspenseRef')
</script>
