import { castAsCursor, castAsPage, PaginationComposable } from '@/core/pagination/base'
import { SortField, SortingComposable } from '@/core/sorting/base'
import { Reactive, Ref, watch } from 'vue'

type WatchHandle = ReturnType<typeof watch>

// ── Types ──

/** Minimal shape the context must satisfy for sync to work. */
export interface ListContextConstraint<F> {
  page?: number
  cursor?: string
  per_page?: number
  search?: string
  sort?: SortField[]
  filters: F
}

export interface ListContextSyncOptions<F extends Record<string, any>> {
  filters?: Reactive<F>
  pagination?: PaginationComposable
  sorting?: SortingComposable
  search?: Ref<string | undefined>
}

// ── Deep assign utility ──

/**
 * Recursively assigns values from `source` to `target` property-by-property.
 * Preserves Vue reactivity on nested objects (e.g. `created_at: { from, to }`)
 * instead of replacing them wholesale.
 *
 * Only iterates own enumerable string keys (skips symbols like SCHEMA_SYMBOL).
 */
function deepAssign(target: Record<string, any>, source: Record<string, any>): void {
  for (const key of Object.keys(source)) {
    const srcVal = source[key]
    const tgtVal = target[key]

    if (
      srcVal !== null &&
      typeof srcVal === 'object' &&
      !Array.isArray(srcVal) &&
      tgtVal !== null &&
      typeof tgtVal === 'object' &&
      !Array.isArray(tgtVal)
    ) {
      deepAssign(tgtVal, srcVal)
    } else {
      target[key] = srcVal
    }
  }
}

// ── Composable ──

/**
 * Bidirectional sync between a unified list context and separated
 * state sources (filters ref, PaginationComposable, etc.).
 *
 * @param context - The unified context ref (required first argument).
 * @param options - Sync channels to wire up. All are optional — only
 *                  provided channels are synced. Extend this interface
 *                  to add new sync channels in the future.
 *
 * @example
 * ```ts
 * useListContextSync(context, {
 *   filters,
 *   pagination,
 * })
 * ```
 */
export function useListContextSync<F extends Record<string, any>>(
  context: Ref<ListContextConstraint<F>>,
  options: ListContextSyncOptions<F>,
): void {
  const watchers: WatchHandle[] = []

  /** Pause all watchers, run `fn`, then resume — prevents bidirectional loops. */
  function guarded(fn: () => void): void {
    watchers.forEach((w) => w.pause())
    fn()
    watchers.forEach((w) => w.resume())
  }

  // ── Filters ──

  if (options.filters) {
    const filters = options.filters

    // Initialization: context filters -> local filters
    deepAssign(filters, toRaw(context.value.filters))

    // filters -> context.filters
    watchers.push(
      watch(
        filters,
        (newVal) => {
          const raw = toRaw(newVal)
          if (JSON.stringify(raw) === JSON.stringify(toRaw(context.value.filters))) return
          guarded(() => deepAssign(context.value.filters as Record<string, any>, raw))
        },
        { deep: true },
      ),
    )

    // context.filters -> filters
    watchers.push(
      watch(
        () => context.value.filters,
        (newVal) => {
          const raw = toRaw(newVal)
          if (JSON.stringify(raw) === JSON.stringify(toRaw(filters))) return
          deepAssign(filters as Record<string, any>, raw)
        },
        { deep: true },
      ),
    )
  }

  // ── Search ──

  if (options.search) {
    const search = options.search

    // Initialization: context search -> local ref
    search.value = context.value.search

    // search -> context.search
    watchers.push(
      watch(search, (newVal) => {
        if (newVal === context.value.search) return
        guarded(() => {
          context.value.search = newVal
        })
      }),
    )

    // context.search -> search
    watchers.push(
      watch(
        () => context.value.search,
        (newVal) => {
          if (newVal === search.value) return
          search.value = newVal
        },
      ),
    )
  }

  // ── Pagination ─────────────────────────────────────────────

  if (options.pagination) {
    const pagination = options.pagination

    // Initialization: context -> pagination.state
    // Direct write because setPage()/setPerPage() are no-ops
    // when state is undefined (before first applyMeta).
    if (typeof context.value.page === 'number') {
      pagination.setPage(context.value.page)
    } else if (typeof context.value.cursor === 'string') {
      pagination.setCursor(context.value.cursor)
    }

    if (pagination.state.value && typeof context.value.per_page === 'number') {
      pagination.setPerPage(context.value.per_page)
    }

    // pagination.state -> context
    watchers.push(
      watch(pagination.state, (state) => {
        guarded(() => {
          context.value.page = castAsPage(state)?.page
          context.value.cursor = castAsCursor(state)?.cursor
          context.value.per_page = state?.per_page
        })
      }),
    )

    // context -> pagination
    watchers.push(
      watch(
        () => [context.value.page, context.value.per_page, context.value.cursor] as const,
        ([newPage, newPerPage, newCursor], [oldPage, oldPerPage, oldCursor]) => {
          if (newPage !== oldPage && newPage !== undefined) {
            pagination.setPage(newPage)
          }
          if (newCursor !== oldCursor && newCursor !== undefined) {
            pagination.setCursor(newCursor)
          }
          if (newPerPage !== oldPerPage && newPerPage !== undefined) {
            pagination.setPerPage(newPerPage)
          }
        },
      ),
    )
  }

  // ── Sorting ──────────────────────────────────────────────────

  if (options.sorting) {
    const sorting = options.sorting

    // Initialization: context sort -> local sorting
    if (context.value.sort?.length) {
      sorting.state.value = [...context.value.sort]
    }

    // sorting.state -> context.sort
    watchers.push(
      watch(sorting.state, (newVal) => {
        const raw = toRaw(newVal)
        if (JSON.stringify(raw) === JSON.stringify(toRaw(context.value.sort ?? []))) return
        guarded(() => {
          context.value.sort = [...raw]
        })
      }),
    )

    // context.sort -> sorting.state
    watchers.push(
      watch(
        () => context.value.sort,
        (newVal) => {
          const raw = toRaw(newVal ?? [])
          if (JSON.stringify(raw) === JSON.stringify(toRaw(sorting.state.value))) return
          sorting.state.value = [...raw]
        },
        { deep: true },
      ),
    )
  }
}
