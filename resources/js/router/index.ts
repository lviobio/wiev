import { contextTemplateMainWithWindows, createMultiRouter } from '@/core/multi-router'
import { createWebHistory } from 'vue-router'
import { createRoutes } from './routes'
// import { useAuthStore } from '@/composables/useAuthStore'

// const GUEST_ROUTES = ['sign-in', 'sign-up']

export const router = createMultiRouter({
  history: () => createWebHistory('app'),
  routes: createRoutes(),
  types: contextTemplateMainWithWindows,
})

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
