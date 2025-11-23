export class LoginData {
  private readonly token: string

  constructor(token: string) {
    this.token = token
  }

  getToken() {
    return this.token
  }
}

export type LoginDataProvided = Ref<LoginData | null>

export const loginInjectKey: InjectionKey<LoginDataProvided> = Symbol()

export function injectLoginData(): LoginDataProvided {
  return inject(loginInjectKey, () => ref(null), true)
}
