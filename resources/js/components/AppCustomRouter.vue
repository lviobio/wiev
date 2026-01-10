<script lang="tsx">
import { getOriginalRouter } from '@/core/navigator/customRouter'
import { useDesktopContext } from '@/core/navigator/useDesktopContext'
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import { useDesktopWindows } from '@/core/navigator/windows/useDesktopWindows'
import { inject } from 'vue'
import { Router, routerKey } from 'vue-router'

export default defineComponent({
  setup(_, { slots }) {
    const router = getOriginalRouter()
    // const originalRoute = router.currentRoute.value
    // const currentRoute = shallowRef(unref(router.currentRoute))
    const windows = useDesktopWindows()
    const injectedKey = inject(DesktopWindowKeySymbol, null)
    const { contextKey } = useDesktopContext()
    // watch(
    //   router.currentRoute,
    //   (newRoute, oldRoute) => {
    //     console.log('router.currentRoute changed', { newRoute, oldRoute })
    //     if (oldRoute === currentRoute.value) {
    //       currentRoute.value = newRoute
    //     }
    //   },
    //   {
    //     deep: true,
    //   },
    // )

    // const push: Router['push'] = async (to) => {
    //   if (typeof (to as any).state === 'object') {
    //     Object.assign(to.state, { [desktopContextKey]: contextKey })
    //   } else {
    //     to.state = { [desktopContextKey]: contextKey }
    //   }
    //   console.log('[AppCustomRouter] push called', { to, contextKey, injectedKey })
    //   // const targetLocation: RouteLocation = router.resolve(to)
    //   // console.log({ targetLocation })
    //   const resolved = router.resolve(to)
    //
    //   let resolvedTitle = ''
    //
    //   if (typeof to === 'object') {
    //     if (to.path === undefined) {
    //       const record = router.getRoutes().find((r) => r.name === to.name)
    //       if (!record) return Promise.reject('Route not found')
    //       const component = (record as any).components?.default ?? (record as any).component
    //
    //       const rawTitle =
    //         (typeof to.windowed === 'object' ? to.windowed.title : undefined) || to.title
    //       resolvedTitle = typeof rawTitle === 'function' ? (rawTitle as any)(to.params) : rawTitle
    //
    //       if (to.windowed === true || typeof to.windowed === 'object') {
    //         windows.open({
    //           // component: component as any,
    //           route: resolved,
    //           // props: to.params !== undefined ? { params: to.params } : undefined,
    //           title: resolvedTitle,
    //         })
    //         return
    //       }
    //     }
    //
    //     if (to.windowed) {
    //       console.warn('[AppCustomRouter] skipped windowed option for route', to)
    //     }
    //
    //     Object.assign(to, { contextKey })
    //   }
    //
    //   if (injectedKey !== null) {
    //     windows.replace(injectedKey, {
    //       route: resolved,
    //       title: resolvedTitle,
    //     })
    //     // currentRoute.value = resolved
    //
    //     return
    //   }
    //
    //   console.log('[AppCustomRouter] calling original push', to)
    //   return router.push(to)
    // }
    //
    // const replace: Router['replace'] = async (to) => {
    //   if (typeof (to as any).state === 'object') {
    //     Object.assign(to.state, { [desktopContextKey]: contextKey })
    //   } else {
    //     to.state = { [desktopContextKey]: contextKey }
    //   }
    //   console.log('[AppCustomRouter] replace called, avoid using it', { to, injectedKey })
    //   // For replace, just delegate to router (multi-context history handles context)
    //   return router.replace(to)
    // }

    // const newHistory = Object.assign({}, router.history, {
    //   contextKey,
    // })
    // const internalInstance = getCurrentInstance()
    // console.log('[AppCustomRouter] internalInstance', internalInstance)

    const customRouter = {
      ...router,
      push(to) {
        return router.callWithSubRouterContextKey(contextKey, () => router.push(to))
      },
      replace(to) {
        return router.callWithSubRouterContextKey(contextKey, () => router.replace(to))
      },
      back() {
        return router.callWithSubRouterContextKey(contextKey, router.back)
      },
      forward() {
        return router.callWithSubRouterContextKey(contextKey, router.forward)
      },
      go(delta) {
        //router.go вызывает history.go, который вызывает listener'ы, которые роутер слушает, и меняет текущую страницу с помощью оригинальных методов push/replace
        //т.к. createRouter возвращает оригинальный роутер, то можно переопределять методы push/replace через Object.assign, и перенести туда логику
        //по проверке находимся ли мы в контексте окна
        return router.callWithSubRouterContextKey(contextKey, () => router.go(delta))
      },
      beforeEach: (guard) => {
        return router.callWithSubRouterContextKey(contextKey, () => {
          return router.beforeEach((...args) =>
            router.callWithSubRouterContextKey(contextKey, () => {
              guard(...args) // .bind(undefined)
            }),
          )
        })
      },
      beforeResolve(guard) {
        return router.callWithSubRouterContextKey(contextKey, () =>
          router.beforeResolve((...args) =>
            router.callWithSubRouterContextKey(contextKey, () => guard(...args)),
          ),
        )
      },
      afterEach(guard) {
        return router.callWithSubRouterContextKey(contextKey, () =>
          router.afterEach((...args) =>
            router.callWithSubRouterContextKey(contextKey, () => guard(...args)),
          ),
        )
      },
      onError(handler) {
        return router.callWithSubRouterContextKey(contextKey, () =>
          router.onError((...args) =>
            router.callWithSubRouterContextKey(contextKey, () => handler(...args)),
          ),
        )
      },
      // currentRoute,
      contextKey,
      custom: true,
    } satisfies Router

    provide(routerKey, customRouter)

    // const reactiveRoute = {} as RouteLocationNormalizedLoaded
    // for (const key in originalRoute) {
    //   Object.defineProperty(reactiveRoute, key, {
    //     get: () => currentRoute.value[key as keyof RouteLocationNormalized],
    //     enumerable: true,
    //   })
    // }
    // provide(routeLocationKey, reactiveRoute)

    return () => slots.default?.()
  },
})
</script>
