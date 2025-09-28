import { routeFromParamsKey } from '@/core/navigator/windows/withPropsSchema'
import type { RouteRecordRaw } from 'vue-router'

type AnyTarget = Record<string, any>

function attachResolver(target: AnyTarget, routeName: string) {
  if (!target[routeFromParamsKey]) {
    target[routeFromParamsKey] = (p: any) => ({ name: routeName, params: p ?? {} })
  }
}

function attachToRoute(route: RouteRecordRaw) {
  if (!route.name) {
    // still ensure children processed
    if (route.children) for (const child of route.children) attachToRoute(child)
    return
  }

  const comp: AnyTarget | undefined = route.component as any
  if (comp) {
    // attach to wrapper/object or function component itself
    attachResolver(comp, route.name as string)
    // and also attach to innerLoader when available
    const inner = (comp as AnyTarget).__innerLoader
    if (inner && typeof inner === 'function') attachResolver(inner, route.name as string)
  }
  if (route.children) {
    for (const child of route.children) attachToRoute(child)
  }
}

export function attachComponentRouteResolvers(routes: RouteRecordRaw[]): RouteRecordRaw[] {
  for (const r of routes) attachToRoute(r)
  return routes
}
