import { HasDataContract, sendAxiosPostRequest } from '@/core/api/simple-repository-helpers-v1/main'
import { AxiosInstance } from 'axios'
import { z } from 'zod'

export const loginFormSchema = z.object({
  email: z.string().nullable(),
  password: z.string().nullable(),
})

export type LoginForm = z.infer<typeof loginFormSchema>

type IssuedCredentials = {
  token: 'bearer'
  credentials: { token: string }
}

export interface LoginResult {
  user: Record<string, unknown>
  issued: IssuedCredentials
}
interface LoginQuery extends HasDataContract<LoginForm> {}
export interface LoginQueryResult extends HasDataContract<LoginResult> {}

export interface AuthenticationRepository {
  login(options: LoginQuery): Promise<LoginQueryResult>
}

class AuthenticationApiRepository implements AuthenticationRepository {
  private readonly axios: AxiosInstance

  constructor() {
    this.axios = useAxios()
  }

  async login(options: LoginQuery) {
    const { data } = await sendAxiosPostRequest<LoginQueryResult>(this.axios, 'auth/login', options)

    return data
  }
}

export function useAuthenticationRepository(): AuthenticationRepository {
  return new AuthenticationApiRepository()
}
