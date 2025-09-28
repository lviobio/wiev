import { propsSchemaKey } from '@/core/navigator/windows/withPropsSchema'
import { Component, defineAsyncComponent, defineComponent, h } from 'vue'
import type { ZodType } from 'zod'

type LoaderWithSchema<
  C extends Component = Component,
  S extends ZodType = ZodType,
> = (() => Promise<C & { [K in typeof propsSchemaKey]: S }>) & { [K in typeof propsSchemaKey]: S }
type LoaderNoSchema<C extends Component = Component> = () => Promise<C>

// Overload: with schema -> wrapper carries schema key
export function routeParamsFallthrough<C extends Component, S extends ZodType>(
  loader: LoaderWithSchema<C, S>,
): Component & { [K in typeof propsSchemaKey]: S } & { __innerLoader?: typeof loader }
// Overload: without schema -> wrapper has no schema key
export function routeParamsFallthrough<C extends Component>(
  loader: LoaderNoSchema<C>,
): Component & { __innerLoader?: typeof loader }
export function routeParamsFallthrough(loader: any): any {
  const AsyncComp = defineAsyncComponent(loader as any)
  const wrapper = defineComponent({
    name: 'RouteParamsFallthrough',
    setup(_props, ctx) {
      const attrs = ctx.attrs || {}
      const route = useRoute()
      return () => h(AsyncComp, { params: route.params, ...attrs })
    },
  }) as any
  // expose inner loader so the central resolver can map loader -> route
  ;(wrapper as any).__innerLoader = loader
  // carry over schema metadata (when present) onto the wrapper for typing
  const schema = (loader as any)[propsSchemaKey]
  if (schema) (wrapper as any)[propsSchemaKey] = schema
  return wrapper as any
}
