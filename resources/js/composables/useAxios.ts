import { injectLoginData } from '@/core/auth'
import { AxiosUtils, UnauthorizedHttpException } from '@/core/errors/axios'
import axios from 'axios'
import { format } from 'date-fns'
import { useNotification } from 'naive-ui'

export const useAxios = () => {
  const loginData = injectLoginData()
  const notification = useNotification()

  const instance = axios.create({
    baseURL: '/api/v1',
  })

  instance.interceptors.request.use((config) => {
    if (loginData.value) {
      config.headers.Authorization = `Bearer ${loginData.value.getToken()}`
    }

    return config
  })

  instance.interceptors.response.use(
    (response) => response,
    (_) => {
      const error = AxiosUtils.toSpecificException(_)

      if (error instanceof UnauthorizedHttpException) {
        //TODO: logout & redirect

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
