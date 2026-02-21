import { isCancel } from '@/core/errors'
import type { MaybePaginatedData } from '@/core/pagination/base'
import { debounce } from 'lodash'
import { onScopeDispose, ref, type Ref, shallowRef, type ShallowRef } from 'vue'

// ── Types ───────────────────────────────────────────────────────

export interface UseListOptions<T> {
  loader: (signal: AbortSignal) => Promise<MaybePaginatedData<T>>
  debounceMs?: number
}

export interface UseListResult<T> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  /** Immediate load (cancels any pending debounce). */
  load: () => Promise<void>
  /** Debounced load (useful for search/filter changes). */
  loadDebounced: () => void
  /** Cancels pending debounce and triggers load immediately. */
  loadImmediate: () => void
  /** Register a handler invoked after each successful load. */
  onAfterLoad: (handler: (result: MaybePaginatedData<unknown>) => void) => void
}

// ── Composable ──────────────────────────────────────────────────

export function useList<T>(options: UseListOptions<T>): UseListResult<T> {
  const { loader, debounceMs = 400 } = options

  const items = shallowRef<T[]>([])
  const loading = ref(false)

  let abortController: AbortController | undefined

  const afterLoadHandlers: ((result: MaybePaginatedData<unknown>) => void)[] = []

  // ── Load functions ────────────────────────────────────────────

  const load = async (): Promise<void> => {
    loading.value = true
    abortController?.abort()
    const currentController = new AbortController()
    abortController = currentController

    try {
      const result = await loader(currentController.signal)
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

  const loadDebouncedFn = debounce(load, debounceMs)
  const loadDebounced = () => {
    loadDebouncedFn()
  }
  const loadImmediate = () => {
    loadDebouncedFn.cancel()
    load()
  }

  // ── Cleanup ───────────────────────────────────────────────────

  onScopeDispose(() => {
    loadDebouncedFn.cancel()
    abortController?.abort()
  })

  return {
    items,
    loading,
    load,
    loadDebounced,
    loadImmediate,
    onAfterLoad: (handler) => afterLoadHandlers.push(handler),
  }
}
