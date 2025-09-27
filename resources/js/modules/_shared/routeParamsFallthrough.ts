import RouterParamsBinder from '@/modules/_shared/RouterParamsBinder.vue'
import { Component, defineAsyncComponent, defineComponent, h } from 'vue'

type AnyLoader = () => Promise<Component>

export function routeParamsFallthrough(loader: AnyLoader) {
  const AsyncComp = defineAsyncComponent(loader)
  const wrapper = defineComponent({
    name: 'RouteParamsFallthrough',
    setup(_props, ctx) {
      const attrs = ctx.attrs || {}
      return () =>
        h(RouterParamsBinder, null, {
          default: (scope: any) => h(AsyncComp, { ...scope, ...attrs }),
        })
    },
  }) as any
  // expose inner loader so the central resolver can map loader -> route
  wrapper.__innerLoader = loader
  return wrapper
}
