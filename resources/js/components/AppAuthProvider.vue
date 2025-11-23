<script lang="tsx">
import { LoginData, loginInjectKey } from '@/core/auth'
import { useLocalStorage } from '@vueuse/core'

export default defineComponent({
  setup(_, { slots }) {
    const loginData = shallowRef<LoginData | null>(null)

    const token = useLocalStorage<string | null>('token', null)

    watch(
      token,
      (value) => {
        if (!value) {
          loginData.value = null
          return
        }

        if (!loginData.value) {
          loginData.value = new LoginData(value)
          return
        }

        loginData.value = new LoginData(value)
      },
      {
        immediate: true,
      },
    )

    watch(loginData, (newValue) => {
      if (!newValue) {
        token.value = null
        return
      }

      token.value = newValue.getToken()
    })

    provide(loginInjectKey, loginData)

    return () => slots.default?.()
  },
})
</script>
