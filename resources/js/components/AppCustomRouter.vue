<script lang="tsx">
import { getOriginalRouter } from '@/core/navigator/customRouter'
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import { useDesktopWindows } from '@/core/navigator/windows/useDesktopWindows'
import { inject } from 'vue'
import { RouteLocation, Router, routerKey } from 'vue-router'

export default defineComponent({
  setup(_, { slots }) {
    const router = getOriginalRouter()
    const windows = useDesktopWindows()
    const injectedKey = inject(DesktopWindowKeySymbol, null)

    const push: Router['push'] = async (to) => {
      const targetLocation: RouteLocation = router.resolve(to)
      console.log({ targetLocation })

      if (typeof to === 'object') {
        if (to.path === undefined) {
          const record = router.getRoutes().find((r) => r.name === to.name)
          if (!record) return Promise.reject('Route not found')
          const component = (record as any).components?.default ?? (record as any).component

          const rawTitle =
            (typeof to.windowed === 'object' ? to.windowed.title : undefined) || to.title
          const resolvedTitle =
            typeof rawTitle === 'function' ? (rawTitle as any)(to.params) : rawTitle

          if (to.windowed === true || typeof to.windowed === 'object') {
            windows.open({
              component: component as any,
              props: to.params !== undefined ? { params: to.params } : undefined,
              title: resolvedTitle,
            })
            return
          }

          if (injectedKey !== null) {
            const props = to.params !== undefined ? { params: to.params } : undefined
            windows.replace(injectedKey, component as any, props, resolvedTitle)

            return
          }
        }
      }

      return router.push(to)
    }

    const customRouter = {
      ...router,
      push,
    } satisfies Router

    provide(routerKey, customRouter)

    return () => slots.default?.()
  },
})
</script>
