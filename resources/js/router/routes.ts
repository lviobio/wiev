import { attachComponentRouteResolvers } from '@/core/navigator/routeResolver'
import { postRoutes } from '@/modules/post/router/routes'
import { RouteRecordRaw } from 'vue-router'

export const createRoutes = () => {
  const routes = [
    {
      path: '/',
      component: () => import('@/components/AppLayout.vue'),
      children: [
        {
          path: '/',
          name: 'home',
          component: () => import('@/pages/PageMain.vue'),
        },
        {
          path: '/logout',
          name: 'logout',
          component: () => import('@/pages/PageLogout.vue'),
        },
        ...postRoutes,
      ],
    },
    {
      path: '/',
      component: () => import('@/components/AuthLayout.vue'),
      children: [
        {
          path: '/sign-in',
          name: 'sign-in',
          component: () => import('@/pages/PageSignIn.vue'),
        },
        {
          path: '/sign-up',
          name: 'sign-up',
          component: () => import('@/pages/PageSignUp.vue'),
        },
      ],
    },
  ] as const satisfies readonly RouteRecordRaw[]

  // Attach route resolvers to async components for window-aware navigation
  return attachComponentRouteResolvers(
    routes as unknown as RouteRecordRaw[],
  ) as unknown as typeof routes
}
