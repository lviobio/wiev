import { routeParamsFallthrough } from '@/modules/_shared/routeParamsFallthrough'
import { loginRouteNames } from '@/modules/app/authentication/router/names'
import Pages from '@/modules/app/authentication/ui/pages'
import { RouteRecordRaw } from 'vue-router'

export const authenticationRoutes = [
  {
    path: '/',
    component: () => import('@/modules/app/authentication/ui/AuthLayout.vue'),
    children: [
      {
        path: 'login',
        name: loginRouteNames.index,
        component: routeParamsFallthrough(Pages.Login),
      },
    ],
  },
] as const satisfies readonly RouteRecordRaw[]
