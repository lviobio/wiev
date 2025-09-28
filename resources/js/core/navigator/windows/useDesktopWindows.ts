import { propsSchemaKey } from '@/core/navigator/windows/withPropsSchema'
import type { AsyncComponentLoader } from 'vue'
import { Component, defineAsyncComponent, markRaw, reactive, readonly } from 'vue'
import type { infer as ZodInfer, ZodType } from 'zod'

export type DesktopWindowItem = {
  windowId: number
  title: string
  x: number
  y: number
  z: number
  width: number
  visible: boolean
  component: Component
  props?: Record<string, any>
}

const state = reactive({
  windows: [] as DesktopWindowItem[],
  nextZ: 1000,
})

function genKey() {
  return Date.now() + Math.random()
}

export function useDesktopWindows() {
  // --- Option types for open() ---

  type OpenBase = {
    title: string | ((p: any) => string)
    component: Component | AsyncComponentLoader<Component>
    width?: number
    x?: number
    y?: number
  }

  // Title helper (supports string or (params)=>string)
  function computeTitle<T>(title: string | ((p: T) => string), params: T | undefined): string {
    return typeof title === 'function'
      ? (title as (p: T) => string)((params as T) ?? (undefined as any))
      : title
  }

  // Shared item creation
  function openInternal<T>(opts: OpenBase, params: T | undefined, typedProps: boolean): number {
    const offset = state.windows.length * 24
    const comp = toRenderable(opts.component)
    const item: DesktopWindowItem = {
      windowId: genKey(),
      title: computeTitle<T>(opts.title, params ?? (opts as any).props?.params),
      x: (opts.x ?? 80) + offset,
      y: (opts.y ?? 80) + offset,
      z: ++state.nextZ,
      width: opts.width ?? 800,
      visible: true,
      component: markRaw(comp),
      props: typedProps ? ({ params } as any) : ((params as any) ?? (opts as any).props ?? {}),
    }
    state.windows.push(item)
    return item.windowId
  }
  type OpenWithSchema<S extends ZodType> = OpenBase & { props: { params: ZodInfer<S> } }
  type ComponentOrLoaderWithSchema<S extends ZodType> =
    | (Component & { [K in typeof propsSchemaKey]: S })
    | (AsyncComponentLoader<Component> & { [K in typeof propsSchemaKey]: S })

  type OpenWithComponentSchema<S extends ZodType, C extends ComponentOrLoaderWithSchema<S>> = Omit<
    OpenBase,
    'component'
  > & {
    component: C
    props: { params: ZodInfer<S> }
  }
  type OpenUntyped = OpenBase & { props?: Record<string, any> }

  // Helper: normalize component/loader into a renderable Component
  function toRenderable(comp: Component | AsyncComponentLoader<Component>): Component {
    const anyComp = comp as any
    if (typeof comp === 'function') {
      // If this is a wrapper that exposes its inner loader, use it
      if (anyComp.__innerLoader && typeof anyComp.__innerLoader === 'function') {
        return defineAsyncComponent(anyComp.__innerLoader as AsyncComponentLoader<Component>)
      }
      // If this function is an actual async loader produced by withPropsSchemaLazy (has __propsSchema)
      if (anyComp[propsSchemaKey]) {
        return defineAsyncComponent(comp as AsyncComponentLoader<Component>)
      }
      // Heuristic: zero-argument functions are likely async loaders (e.g., () => import('...'))
      if ((comp as (...args: unknown[]) => unknown).length === 0) {
        return defineAsyncComponent(comp as AsyncComponentLoader<Component>)
      }
      // If it's an async function, treat it as a loader too
      if ((comp as any).constructor && (comp as any).constructor.name === 'AsyncFunction') {
        return defineAsyncComponent(comp as AsyncComponentLoader<Component>)
      }
      // Otherwise it's a normal (possibly functional) component
      return comp as Component
    }
    return comp as Component
  }

  // Overload 1: Provide propsSchema -> props must be { params: z.infer<typeof propsSchema> }
  function open<S extends ZodType>(opts: OpenWithSchema<S>): number
  // Overload 1b: Component statically carries schema on __propsSchema
  function open<S extends ZodType, C extends ComponentOrLoaderWithSchema<S>>(
    opts: OpenWithComponentSchema<S, C>,
  ): number
  // Overload 2: Generic fallback (untyped props)
  function open(opts: OpenUntyped): number
  // Impl
  function open(
    opts:
      | OpenWithSchema<ZodType>
      | OpenWithComponentSchema<ZodType, ComponentOrLoaderWithSchema<ZodType>>
      | OpenUntyped,
  ): number {
    // Typed path (schema provided via component)
    const anyOpts: any = opts
    if (
      anyOpts.component &&
      (anyOpts.component as any)[propsSchemaKey] &&
      anyOpts.props?.params !== undefined
    ) {
      return openInternal<any>(opts as OpenBase, anyOpts.props.params, true)
    }
    // Typed path (explicit typed props object without component schema)
    if ((opts as any).props?.params !== undefined) {
      return openInternal<any>(opts as OpenBase, (opts as any).props.params, true)
    }
    // Fallback untyped
    return openInternal<any>(opts as OpenBase, (opts as any).props as any, false)
  }

  function close(windowId: number) {
    const idx = state.windows.findIndex((w) => w.windowId === windowId)
    if (idx !== -1) state.windows.splice(idx, 1)
  }

  function bringToFront(windowId: number) {
    const w = state.windows.find((w) => w.windowId === windowId)
    if (!w) return
    w.z = ++state.nextZ
  }

  function updateX(windowId: number, x: number) {
    const w = state.windows.find((w) => w.windowId === windowId)
    if (!w) return
    w.x = x
  }

  function updateY(windowId: number, y: number) {
    const w = state.windows.find((w) => w.windowId === windowId)
    if (!w) return
    w.y = y
  }

  // Returns a callback that accepts params and opens a window with correct typing
  // 0) Infer params from component.[propsSchemaKey] (preferred overload)
  function openable<C extends { [propsSchemaKey]: ZodType }>(
    opts: Omit<OpenBase, 'component' | 'title'> & {
      component: C
      title: string | ((p: ZodInfer<C[typeof propsSchemaKey]>) => string)
    },
  ): (params: ZodInfer<C[typeof propsSchemaKey]>) => number
  function openable<S extends ZodType, C extends ComponentOrLoaderWithSchema<S>>(
    opts: Omit<OpenWithComponentSchema<S, C>, 'props' | 'title'> & {
      title: string | ((p: ZodInfer<S>) => string)
    },
  ): (params: ZodInfer<S>) => number
  function openable<P = any>(opts: OpenBase): (params?: P) => number
  function openable(opts: any): (params?: any) => number {
    const base = opts as OpenBase
    return (params?: any) =>
      openInternal<any>(base, params, !!(base as any).component?.[propsSchemaKey])
  }

  function replace(
    windowId: number,
    component: Component | AsyncComponentLoader<Component>,
    props?: Record<string, any>,
    title?: string,
  ) {
    console.log('replacing', props)
    const w = state.windows.find((w) => w.windowId === windowId)
    if (!w) return
    w.component = markRaw(toRenderable(component))
    if (props !== undefined) w.props = props
    if (title !== undefined) w.title = title
    w.z = ++state.nextZ
  }

  return {
    windows: readonly(state.windows),
    open,
    close,
    bringToFront,
    updateX,
    updateY,
    openable,
    replace,
  }
}
