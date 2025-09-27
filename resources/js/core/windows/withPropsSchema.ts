// Shared metadata key for attaching zod schema to components/loaders
export const propsSchemaKey = '__propsSchema' as const
export const routeFromParamsKey = '__routeFromParams' as const

import type { Component } from 'vue'
import type { infer as ZodInfer, ZodType } from 'zod'

export function withPropsSchema<C extends Component, S extends ZodType>(
  component: C,
  schema: S,
  routeFromParams?: (p: ZodInfer<S>) => any,
): C & { [K in typeof propsSchemaKey]: S } {
  ;(component as any)[propsSchemaKey] = schema
  if (routeFromParams) (component as any)[routeFromParamsKey] = routeFromParams
  return component as any
}

// withPropsSchemaLazy: returns a loader function with attached metadata (__propsSchema and optional __routeFromParams)
type PageLoader<C extends Component, S extends ZodType> = (() => Promise<
  C & { [K in typeof propsSchemaKey]: S }
>) & {
  [propsSchemaKey]: S
  [routeFromParamsKey]?: (p: ZodInfer<S>) => any
}

export function withPropsSchemaLazy<C extends Component, S extends ZodType>(
  loader: () => Promise<{ default: C }>,
  schema: S,
  routeFromParams?: (p: ZodInfer<S>) => any,
): PageLoader<C, S> {
  const wrapped = (async () => {
    const m = await loader()
    return withPropsSchema<C, S>(m.default, schema, routeFromParams)
  }) as any

  ;(wrapped as any)[propsSchemaKey] = schema
  if (routeFromParams) (wrapped as any)[routeFromParamsKey] = routeFromParams
  return wrapped as PageLoader<C, S>
}
