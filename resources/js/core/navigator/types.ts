// Utility types to derive params from components that carry a zod schema
import { propsSchemaKey } from '@/core/navigator/windows/withPropsSchema'
import type { RouteRecordRaw } from 'vue-router'
import type { infer as ZodInfer, ZodType } from 'zod'

type PropsSchemaOf<C> = C extends { [K in typeof propsSchemaKey]: infer S } ? S : never

type ParamsOfComponent<C> =
  PropsSchemaOf<C> extends ZodType<any, any, any>
    ? ZodInfer<PropsSchemaOf<C>>
    : Record<string, never>

// Extract a route entry of interest: name and component type
type RouteEntry<R extends RouteRecordRaw> = R extends { name: infer N; component: infer C }
  ? N extends string
    ? { name: N; component: C }
    : never
  : never

// Two-level flatten: entries at root and their direct children
type RouteOf<RS> = RS extends ReadonlyArray<infer R> ? R : never
type ChildrenOfArray<RS> =
  RS extends ReadonlyArray<infer R>
    ? R extends { children?: infer C }
      ? C extends ReadonlyArray<any>
        ? C
        : never
      : never
    : never
// Explicit flatten: root, children, and grandchildren
type L0<RS> = RouteEntry<Extract<RouteOf<RS>, RouteRecordRaw>>
// General recursive flatten with depth limit (up to 10 levels)
type Depth = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10
type DecMap = { 0: 0; 1: 0; 2: 1; 3: 2; 4: 3; 5: 4; 6: 5; 7: 6; 8: 7; 9: 8; 10: 9 }
type Dec<D extends Depth> = D extends keyof DecMap ? DecMap[D] : 0

export type FlattenRoutes<RS, D extends Depth = 10> =
  | L0<RS>
  | (D extends 0 ? never : FlattenRoutes<ChildrenOfArray<RS>, Dec<D>>)

// Build the RouteParamMap from a union of RouteEntry, narrowing per key with Extract
type KeysOf<U> = U extends { name: infer N } ? (N extends string ? N : never) : never
type ValueFor<U, K> =
  Extract<U, { name: K }> extends { component: infer C }
    ? { params: ParamsOfComponent<C> }
    : { params: Record<string, never> }
export type BuildMap<U> = { [K in KeysOf<U>]: ValueFor<U, K> }
