import { useAuthStore } from '@/composables/useAuthStore'
import { useAxios } from '@/composables/useAxios'
import { User } from '@/core/types'
import { useMessage } from 'naive-ui'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const user = ref<User | null>(null)

export const useUserStore = () => {
  const axios = useAxios()
  const authStore = useAuthStore()
  const router = useRouter()
  const message = useMessage()

  const authorize = async (data: object) => {
    const response = await axios.post<string>('/sign-in', data)

    if (response.status === 200) {
      authStore.authorizeByToken(response.data)

      await router.push({ name: 'home' })

      message.success('Success login')
    }
  }

  const register = async (data: object) => {
    const response = await axios.post<{ data: User }>('/sign-up', data)

    if (response.status === 200) {
      message.success('Registered successfully')

      await router.push({ name: 'sign-in' })
    }
  }

  const getCurrentUser = async () => {
    const response = await axios.get<{ data: User }>('/user')

    user.value = response.data.data
  }

  return {
    user,
    getCurrentUser,
    authorize,
    register,
  }
}
