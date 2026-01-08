<script lang="ts">
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import { useDesktopWindows } from '@/core/navigator/windows/useDesktopWindows'
import { inject } from 'vue'
import {
  MatcherLocation,
  routeLocationKey,
  RouteLocationNormalized,
  RouteLocationNormalizedLoaded,
  RouteLocationNormalizedLoadedGeneric,
  Router,
  routerKey,
  routerViewLocationKey,
  viewDepthKey,
} from 'vue-router'

export default defineComponent({
  setup(_, { slots }) {
    const router = useRouter()
    const mgr = useDesktopWindows()

    const windowKey = inject(DesktopWindowKeySymbol)

    if (!windowKey) {
      throw new Error('Window key not found')
    }

    const window = mgr.get(windowKey)!

    const originalRoute = router.currentRoute.value
    const currentRoute = shallowRef({} as RouteLocationNormalizedLoadedGeneric)

    const viewDepthRef = ref(0)
    provide(viewDepthKey, viewDepthRef)

    watch(
      window.route,
      (newRoute) => {
        currentRoute.value = newRoute || {}

        if (newRoute) {
          const found = (newRoute as unknown as MatcherLocation).matched.findLastIndex(
            (m) => m.meta.windowRoot,
          )

          viewDepthRef.value =
            found !== -1 ? found : (newRoute as unknown as MatcherLocation).matched.length - 1
        }
      },
      { immediate: true },
    )

    watch(router.currentRoute, (newCurrentRoute) => {
      currentRoute.value = newCurrentRoute
    })

    watch(currentRoute, () => {
      console.log('currentRoute changed', currentRoute.value)
    })

    if (window.route) {
      const reactiveRoute = {} as RouteLocationNormalizedLoaded
      for (const key in originalRoute) {
        Object.defineProperty(reactiveRoute, key, {
          get: () => currentRoute.value[key as keyof RouteLocationNormalized],
          enumerable: true,
        })
      }

      const customRouter = {
        ...router,
        currentRoute,
      } satisfies Router

      provide(routerKey, customRouter)
      provide(routeLocationKey, reactiveRoute)
      provide(routerViewLocationKey, currentRoute)
    } else {
      provide(routerKey, undefined as any)
      provide(routeLocationKey, undefined as any)
    }

    return () => slots.default?.()
  },
})
</script>
