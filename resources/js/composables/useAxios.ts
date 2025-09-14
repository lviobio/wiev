import { useAuthStore } from '@/composables/useAuthStore'
import axios from 'axios'
import { format } from 'date-fns'
import { useNotification } from 'naive-ui'

export const useAxios = () => {
  const authStore = useAuthStore()
  const notification = useNotification()

  const instance = axios.create({
    baseURL: '/api',
  })

  instance.interceptors.request.use((config) => {
    if (authStore.token.value) {
      config.headers.Authorization = `Bearer ${authStore.token.value}`
    }

    return config
  })

  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        authStore.logout()

        window.location.reload()
      }

      if (
        error.response &&
        error.response.data &&
        typeof error.response.data === 'object' &&
        'message' in error.response.data
      ) {
        notification.error({
          title: 'Error',
          content: error.response.data.message as string,
          meta: format(new Date(), 'dd.MM.yyyy HH:mm'),
          duration: 5000,
        })
      }

      return Promise.reject(error)
    },
  )

  return instance
}
