import { useDesktopWindows } from '@/core/windows/useDesktopWindows'
import { DesktopWindowKeySymbol } from '@/core/windows/WindowContext.vue'
import { propsSchemaKey, routeFromParamsKey } from '@/core/windows/withPropsSchema'
import type { AsyncComponentLoader, Component } from 'vue'
import { inject } from 'vue'
import { useRouter } from 'vue-router'
import type { infer as ZodInfer, ZodType } from 'zod'

export type SwapTargetBase = {
  title?: string
}

export type SwapTargetWithSchema<S extends ZodType> = SwapTargetBase & {
  component: Component | AsyncComponentLoader<Component>
  props: { params: ZodInfer<S> }
}

// Untyped variant: for pages without a schema (e.g., List.Page)
export type SwapTargetUntyped = SwapTargetBase & {
  component: Component | AsyncComponentLoader<Component>
  props?: Record<string, any>
}

export type NavigateTarget = { route: any }

export function useAppNavigator() {
  const router = useRouter()
  const windows = useDesktopWindows()
  const injectedKey = inject<number | null>(DesktopWindowKeySymbol as any, null)

  function navigateOrSwap<S extends ZodType>(
    target: NavigateTarget | SwapTargetWithSchema<S> | SwapTargetUntyped,
  ): boolean {
    const key = injectedKey

    // If inside a window and a component target is provided -> swap
    if (key != null && 'component' in target && (target as any).component) {
      windows.replace(
        key,
        (target as any).component as any,
        (target as any).props,
        (target as any).title,
      )
      return true
    }

    // Outside window: if no route provided but component has route resolver, derive it
    if (
      (!('route' in target) || !(target as any).route) &&
      'component' in target &&
      (target as any).component
    ) {
      const compAny = (target as any).component as any
      const params = (target as any).props?.params
      if (compAny && compAny[routeFromParamsKey]) {
        const derived = compAny[routeFromParamsKey](params)
        if (derived) {
          router.push(derived)
          return true
        }
      }
    }

    // Fallback: use explicit route if provided
    if ('route' in target && (target as any).route) {
      router.push((target as any).route)
      return true
    }

    // If no route and not inside a window, do nothing to avoid errors
    return false
  }

  // openOrNavigate: swaps current window content when inside window; otherwise navigates.
  // Overload 1 (typed): component carries schema, params are inferred from schema
  function openOrNavigate<C extends { [K in typeof propsSchemaKey]: ZodType }>(opts: {
    component: C
    params: ZodInfer<C[typeof propsSchemaKey]>
    title?: string
    route?: any // optional explicit route fallback when component lacks resolver
  }): boolean
  // Overload 2 (untyped): component without schema
  function openOrNavigate(opts: {
    component: Component | AsyncComponentLoader<Component>
    params?: Record<string, any>
    title?: string
    route?: any
  }): boolean
  function openOrNavigate(opts: any): boolean {
    const key = injectedKey
    const compAny = opts.component as any
    const params = opts.params

    // Inside window -> swap content
    if (key != null) {
      const props = compAny && compAny[propsSchemaKey] ? { params } : (opts.params ?? undefined)
      windows.replace(key, opts.component as any, props, opts.title)
      return true
    }

    // Outside window -> prefer auto-derived route if available
    if (compAny && compAny[routeFromParamsKey]) {
      const derived = compAny[routeFromParamsKey](params)
      if (derived) {
        router.push(derived)
        return true
      }
    }
    // Fallback to explicit route when provided
    if (opts.route) {
      router.push(opts.route)
      return true
    }
    return false
  }

  return { navigateOrSwap, openOrNavigate }
}
