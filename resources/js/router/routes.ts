import { attachComponentRouteResolvers } from '@/core/router/routeResolver'
import { postRoutes } from '@/modules/post/routes'
import { RouteRecordRaw } from 'vue-router'

export const createRoutes = (): RouteRecordRaw[] => {
  const routes: RouteRecordRaw[] = [
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
  ]

  // Attach route resolvers to async components for window-aware navigation
  return attachComponentRouteResolvers(routes)
}
