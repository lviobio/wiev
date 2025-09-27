<template>
  <div class="flex flex-col items-center justify-center p-4 text-center">
    <NResult :status="errorCode" size="large">
      <template #icon>
        <ErrorCircle16Regular :type="errorType" />
      </template>

      <div class="mb-4 text-2xl font-bold text-gray-800">
        {{ errorTitle }}
      </div>

      <div class="mb-6 text-gray-400">
        <p v-if="props.error.message" class="mb-2" :class="errorMessageClasses">
          {{ props.error.message }}
        </p>
        <p v-else>{{ errorDescription }}</p>

        <!-- Network error specific content -->
        <div v-if="isNetworkErrorType" class="mt-4 text-sm">
          <p>{{ 'check_connection' }}</p>

          <ul class="mx-auto mt-2 max-w-md list-inside list-disc text-left">
            <li>{{ 'check_internet' }}</li>
            <li>{{ 'check_firewall' }}</li>
            <li>{{ 'try_later' }}</li>
          </ul>
        </div>
      </div>

      <div class="flex justify-center gap-3">
        <NButton v-if="dataHistory.state.back" strong secondary @click="router.back">
          {{ 'back' }}
        </NButton>

        <NButton v-if="retryAvailable" type="primary" @click="emit('retry')">
          {{ 'retry' }}
        </NButton>

        <RouterLink :to="{ name: 'home' }" v-if="route.name !== 'home'">
          <NButton type="primary">
            {{ 'go_home' }}
          </NButton>
        </RouterLink>
      </div>
    </NResult>
  </div>
</template>

<script setup lang="ts">
import {
  isForbidden,
  isModelNotFound,
  isNetworkError,
  isNotFound,
  isSystemError,
  isValidationFailed,
} from '@/core/errors'
import { ErrorCircle16Regular } from '@vicons/fluent'

const props = defineProps<{
  error: Error
}>()
const emit = defineEmits<{
  retry: []
}>()

const router = useRouter()
const route = useRoute()

const dataHistory = ref(history)

const errorCode = computed((): 'warning' | 'error' | '500' | '404' | '403' | '418' => {
  const error = props.error

  if (isNotFound(error) || isModelNotFound(error)) {
    return '404'
  } else if (isValidationFailed(error)) {
    return 'warning'
  } else if (isSystemError(error)) {
    return '500'
  } else if (isForbidden(error)) {
    return '403'
  } else if (isNetworkError(error)) {
    return 'error'
  }

  return 'error'
})

// Определяем тип ошибки для иконки на основе errorCode
const errorType = computed(() => {
  const code = errorCode.value

  switch (code) {
    case '404':
      return '404'
    case '500':
      return '500'
    case '403':
      return '403'
    case 'warning':
      return 'warning'
    default:
      // Для сетевых ошибок и других используем 'network'
      if (isNetworkError(props.error)) {
        return 'network'
      }
      return 'error'
  }
})

// Определяем заголовок ошибки на основе errorCode
const errorTitle = computed(() => {
  const code = errorCode.value

  switch (code) {
    case '404':
      return 'not_found_title'
    case '500':
      return 'server_error_title'
    case '403':
      return 'forbidden_title'
    case 'warning':
      return 'validation_error_title'
    default:
      if (isNetworkError(props.error)) return 'network_error_title'
      return 'error_title'
  }
})

// Определяем описание ошибки на основе errorCode
const errorDescription = computed(() => {
  const code = errorCode.value

  switch (code) {
    case '404':
      return 'not_found_description'
    case '500':
      return 'server_error_description'
    case '403':
      return 'forbidden_description'
    case 'warning':
      return 'validation_error_description'
    default:
      if (isNetworkError(props.error)) return 'network_error_description'
      return 'error_description'
  }
})

// Проверяем, является ли ошибка сетевой для отображения специального контента
const isNetworkErrorType = computed(() => {
  return isNetworkError(props.error)
})

// Классы для сообщения об ошибке
const errorMessageClasses = computed(() => {
  const code = errorCode.value

  if (code === '500' || isNetworkError(props.error)) {
    return 'font-mono text-sm bg-gray-100 p-2 rounded'
  }

  return ''
})

const retryAvailable = computed(() => {
  const error = props.error

  if (isNotFound(error) || isModelNotFound(error)) {
    return false
  } else if (isValidationFailed(error)) {
    return false
  } else if (isForbidden(error)) {
    return false // 403 ошибки обычно не решаются retry
  }

  return true
})
</script>
