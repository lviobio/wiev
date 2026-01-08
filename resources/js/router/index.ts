import { DesktopContextManagerInstance } from '@/core/navigator/contextManager'
import { createMultiContextHistory } from '@/core/navigator/multiContextHistory'
import { createRouter, createWebHistory } from 'vue-router'
import { createRoutes } from './routes'
// import { useAuthStore } from '@/composables/useAuthStore'

// const GUEST_ROUTES = ['sign-in', 'sign-up']

export const createAppRouter = () => {
  const contextManager = new DesktopContextManagerInstance()

  const history = createMultiContextHistory(() => createWebHistory('app'), contextManager)
  const router = createRouter({
    // history: baseHistory,
    history,
    routes: createRoutes(),
  })

  history.onRouterResolved(router)

  return contextManager.routerPlugin(router)
}

// router.beforeEach((to) => {
//   const authStore = useAuthStore()
//
//   if (!authStore.loggedIn.value && !GUEST_ROUTES.includes(String(to.name))) {
//     return { name: 'sign-in' }
//   }
//
//   if (authStore.loggedIn.value && GUEST_ROUTES.includes(String(to.name))) {
//     return { name: 'home' }
//   }
// })
