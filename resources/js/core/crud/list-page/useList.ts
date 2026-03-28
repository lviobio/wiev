import { isCancel } from '@/core/errors'
import type { MaybePaginatedData } from '@/core/pagination/base'
import { debounce } from 'lodash'
import { onScopeDispose, ref, type Ref, shallowRef, type ShallowRef } from 'vue'
import type { FeatureContext, ListFeature, MergedState } from './features'

// ── Types ───────────────────────────────────────────────────────

export interface UseListLoaderParams<Features extends readonly ListFeature[]> {
  features: MergedState<Features>
  signal: AbortSignal
}

export interface UseListOptions<T, Features extends readonly ListFeature[]> {
  features: [...Features]
  loader: (params: UseListLoaderParams<Features>) => Promise<MaybePaginatedData<T>>
  debounceMs?: number
}

export interface UseListBaseResult<T> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  load: () => Promise<void>
}

export type UseListResult<T, Features extends readonly ListFeature[]> = UseListBaseResult<T> &
  MergedState<Features>

// ── Composable ──────────────────────────────────────────────────

export function useList<T, const Features extends readonly ListFeature[]>(
  options: UseListOptions<T, Features>,
): UseListResult<T, Features> {
  const { loader, debounceMs = 400 } = options

  const items = shallowRef<T[]>([])
  const loading = ref(false)

  let abortController: AbortController | undefined

  // ── Lifecycle registries ──────────────────────────────────────

  const afterLoadHandlers: ((result: MaybePaginatedData<unknown>) => void)[] = []

  // ── Load functions ────────────────────────────────────────────

  const load = async (): Promise<void> => {
    loading.value = true
    abortController?.abort()
    const currentController = new AbortController()
    abortController = currentController

    try {
      const loaderParams = {
        features: mergedState as MergedState<Features>,
        signal: currentController.signal,
      }
      const result = await loader(loaderParams)
      items.value = result.data

      for (const handler of afterLoadHandlers) {
        handler(result)
      }
    } catch (e: unknown) {
      if (isCancel(e)) {
        return
      }

      throw e
    } finally {
      if (abortController === currentController) {
        loading.value = false
      }
    }
  }

  const loadDebounced = debounce(load, debounceMs)
  const loadImmediate = () => {
    loadDebounced.cancel()
    load()
  }

  // ── Feature context ───────────────────────────────────────────

  const shared = new Map<symbol, unknown>()

  const ctx: FeatureContext = {
    loadDebounced,
    loadImmediate,
    onAfterLoad: (handler) => afterLoadHandlers.push(handler),
    provide: (key, value) => shared.set(key, value),
    resolve: <T = unknown>(key: symbol) => shared.get(key) as T | undefined,
  }

  // ── Install features ──────────────────────────────────────────

  const mergedState = {} as Record<string, unknown>

  for (const feature of options.features) {
    const state = feature.install(ctx)
    Object.assign(mergedState, state)
  }

  // ── Cleanup ───────────────────────────────────────────────────

  onScopeDispose(() => {
    loadDebounced.cancel()
    abortController?.abort()
  })

  // ── Return ────────────────────────────────────────────────────

  return {
    items,
    loading,
    load,
    ...mergedState,
  } as UseListResult<T, Features>
}
