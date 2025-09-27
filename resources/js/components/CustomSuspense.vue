<template>
  <template v-if="capturedError">
    <slot name="error" :error="capturedError" />
  </template>
  <Suspense
    v-else
    :key="keyRef"
    @pending="handlePending"
    @resolve="handleResolve"
    @fallback="handleFallback"
  >
    <template #fallback>
      <slot name="loading" />
    </template>

    <slot />
  </Suspense>
</template>

<script setup lang="ts">
import { onErrorCaptured } from 'vue'

export type CustomSuspenseInst = {
  retry: () => void
}

const route = useRoute()

const props = defineProps<{
  transform?: (error: Error) => Error
  validate?: (error: Error) => boolean
}>()

defineSlots<{
  default: () => Element
  loading: () => Element
  error: (data: { error: Error }) => Element
}>()

defineExpose<CustomSuspenseInst>({
  retry: () => {
    keyRef.value++
    capturedError.value = undefined
  },
})

watch(
  () => route.fullPath,
  () => {
    capturedError.value = undefined
  },
)

const keyRef = ref(0)
const capturedError = ref<Error>()
const loading = ref(false)

onErrorCaptured((err) => {
  if (props.validate?.(err) === false) return true

  capturedError.value = props.transform ? props.transform(err) : err

  // When false is returned, the error will be not propagated to parent onErrorCaptured hooks
  return false
})

const handlePending = () => {
  capturedError.value = undefined
  loading.value = true
}
const handleResolve = () => {
  loading.value = false
}
const handleFallback = () => {
  loading.value = true
}
</script>
