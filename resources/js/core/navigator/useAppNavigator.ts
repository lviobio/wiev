import type { RouteParamMap } from '@/core/navigator/routeParamMap'
import { DesktopWindowKeySymbol } from '@/core/navigator/windows/symbols'
import { useDesktopWindows } from '@/core/navigator/windows/useDesktopWindows'
import { routeFromParamsKey } from '@/core/navigator/windows/withPropsSchema'
import { inject } from 'vue'
import { useRouter } from 'vue-router' // navigate: swaps current window content when inside window; otherwise navigates.

interface CurrentPage {
  route: string
}

export function useAppNavigator() {
  const router = useRouter()
  const windows = useDesktopWindows()
  const injectedKey = inject(DesktopWindowKeySymbol, null)

  type ParamOf<K extends keyof RouteParamMap> = RouteParamMap[K]['params']
  type KeysWithoutParams = {
    [K in keyof RouteParamMap]: ParamOf<K> extends never ? K : never
  }[keyof RouteParamMap]
  type KeysWithParams = Exclude<keyof RouteParamMap, KeysWithoutParams>

  // Swaps current window content when inside window; otherwise navigates by route name.
  // Overloads preserve params typing and allow omission for routes without schema
  async function navigate<K extends KeysWithParams>(opts: {
    name: K
    params: ParamOf<K>
    title?: string | ((p: ParamOf<K>) => string)
    windowed?: boolean
  }): Promise<boolean>
  async function navigate<K extends KeysWithoutParams>(opts: {
    name: K
    title?: string | ((p: never) => string)
    windowed?: boolean
  }): Promise<boolean>
  async function navigate(opts: {
    name: keyof RouteParamMap
    params?: Record<string, any>
    title?: any
    windowed?: boolean
  }): Promise<boolean> {
    const params = (opts as any).params
    const record = router.getRoutes().find((r) => r.name === opts.name)
    if (!record) return false
    const component = (record as any).components?.default ?? (record as any).component
    const resolvedTitle =
      typeof opts.title === 'function' ? (opts.title as any)(params) : opts.title
    // Force opening a new window regardless of current context
    if (opts.windowed) {
      windows.open({
        component: component as any,
        props: params !== undefined ? { params } : undefined,
        title: resolvedTitle ?? String(opts.name),
      } as any)
      return true
    }
    if (injectedKey !== null) {
      // Always forward params to page in window context; schema-less pages can ignore it.
      const props = params !== undefined ? { params } : undefined
      windows.replace(injectedKey, component as any, props, resolvedTitle)
      return true
    }
    if (params !== undefined) await router.push({ name: opts.name as string, params })
    else await router.push({ name: opts.name as string })
    return true
  }
  //

  function getCurrentPage(): CurrentPage {
    // If inside a window, try to resolve the window page route via component's resolver
    if (injectedKey !== null) {
      const w = (windows as any).windows?.find((it: any) => it.windowId === injectedKey)
      if (w) {
        const compAny = w.component as any
        const params = (w.props as any)?.params
        if (compAny && compAny[routeFromParamsKey]) {
          const derived = compAny[routeFromParamsKey](params)
          if (derived) return { route: String(derived.name ?? derived.path ?? '') }
        }
      }
    }
    // Fallback to current router route
    const currentRoute = router.currentRoute.value
    return { route: String(currentRoute.name ?? currentRoute.path ?? '') }
  }

  // delayedNavigate: returns a callable that triggers navigate with provided params
  // Dynamic params variant (only for routes with params)
  function delayedNavigate<K extends KeysWithParams>(opts: {
    name: K
    title?: string | ((p: ParamOf<K>) => string)
    windowed?: boolean
  }): (params: ParamOf<K>) => Promise<boolean>
  // Fixed params variant (routes with params)
  function delayedNavigate<K extends KeysWithParams>(opts: {
    name: K
    params: ParamOf<K>
    title?: string | ((p: ParamOf<K>) => string)
    windowed?: boolean
  }): () => Promise<boolean>
  // Routes without params: no params in returned callable
  function delayedNavigate<K extends KeysWithoutParams>(opts: {
    name: K
    title?: string | ((p: never) => string)
    windowed?: boolean
  }): () => Promise<boolean>
  function delayedNavigate(opts: any): any {
    if ('params' in opts && opts.params !== undefined) {
      const fixed = opts as {
        name: string
        params: Record<string, any>
        title?: any
        windowed?: boolean
      }
      return () => (navigate as any)(fixed) as Promise<boolean>
    }
    const base = opts as { name: string; title?: any; windowed?: boolean }
    return (params?: Record<string, any>) =>
      params !== undefined ? (navigate as any)({ ...base, params }) : (navigate as any)(base)
  }

  return { navigate, delayedNavigate, getCurrentPage }
}
