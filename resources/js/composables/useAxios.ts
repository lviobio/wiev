import { useAuthStore } from '@/composables/useAuthStore'
import { AxiosUtils, UnauthorizedHttpException } from '@/core/errors/axios'
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
    (_) => {
      const error = AxiosUtils.toSpecificException(_)

      if (error instanceof UnauthorizedHttpException) {
        authStore.logout()
        window.location.reload()

        return
      }

      if (error.message) {
        notification.error({
          title: 'Error',
          content: error.message,
          meta: format(new Date(), 'dd.MM.yyyy HH:mm'),
          duration: 5000,
        })
      }

      return Promise.reject(error)
    },
  )

  return instance
}
