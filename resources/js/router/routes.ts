import { attachComponentRouteResolvers } from '@/core/navigator/routeResolver'
import { authenticationRoutes } from '@/modules/app/authentication/router/routes'
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
    ...authenticationRoutes,
  ] as const satisfies readonly RouteRecordRaw[]

  // Attach route resolvers to async components for window-aware navigation
  return attachComponentRouteResolvers(
    routes as unknown as RouteRecordRaw[],
  ) as unknown as typeof routes
}
