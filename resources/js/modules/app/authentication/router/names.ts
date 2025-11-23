import { ModuleRouteGenerator } from '@/modules/_shared/interface'

export const loginRouteNames = {
  index: 'login.index' as const,
}

export const loginRouteGenerator = {
  index: () => ({
    name: loginRouteNames.index,
  }),
} satisfies ModuleRouteGenerator
