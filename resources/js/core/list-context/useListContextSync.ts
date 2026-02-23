import { castAsCursor, castAsPage, PaginationComposable } from '@/core/pagination/base'
import { nextTick, Ref, toRaw, watch } from 'vue'

// ── Types ──────────────────────────────────────────────────────

/** Minimal shape the context must satisfy for sync to work. */
interface ListContextConstraint<F> {
  page?: number
  cursor?: string
  per_page?: number
  filters: F
}

export interface ListContextSyncOptions<F extends Record<string, any>> {
  filters?: Ref<F>
  pagination?: PaginationComposable
}

// ── Deep assign utility ────────────────────────────────────────

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

// ── Composable ─────────────────────────────────────────────────

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
  let isSyncing = false

  function guard(fn: () => void): void {
    if (isSyncing) return
    isSyncing = true
    fn()
    nextTick(() => {
      isSyncing = false
    })
  }

  // ── Filters ────────────────────────────────────────────────

  if (options.filters) {
    const filters = options.filters

    // Initialization: context filters -> local filters
    deepAssign(filters.value, toRaw(context.value.filters))

    // filters -> context.filters
    watch(
      filters,
      (newVal) => {
        guard(() => {
          const raw = toRaw(newVal)
          if (JSON.stringify(raw) === JSON.stringify(toRaw(context.value.filters))) return
          deepAssign(context.value.filters as Record<string, any>, raw)
        })
      },
      { deep: true },
    )

    // context.filters -> filters
    watch(
      () => context.value.filters,
      (newVal) => {
        guard(() => {
          const raw = toRaw(newVal)
          if (JSON.stringify(raw) === JSON.stringify(toRaw(filters.value))) return
          deepAssign(filters.value as Record<string, any>, raw)
        })
      },
      { deep: true },
    )
  }

  // ── Pagination ─────────────────────────────────────────────

  if (options.pagination) {
    const pagination = options.pagination

    // Initialization: context -> pagination.state
    // Direct write because setPage()/setPerPage() are no-ops
    // when state is undefined (before first applyMeta).
    if (context.value.page != null || context.value.per_page != null) {
      pagination.state.value = {
        type: 'page',
        page: context.value.page,
        per_page: context.value.per_page,
      }
    } else if (context.value.cursor != null) {
      pagination.state.value = {
        type: 'cursor',
        cursor: context.value.cursor,
        per_page: context.value.per_page,
      }
    }

    // pagination.state -> context
    watch(pagination.state, (state) => {
      guard(() => {
        const page = castAsPage(state)
        const cursor = castAsCursor(state)
        context.value.page = page?.page
        context.value.cursor = cursor?.cursor
        context.value.per_page = state?.per_page
      })
    })

    // context -> pagination
    watch(
      () => [context.value.page, context.value.per_page, context.value.cursor] as const,
      ([page, perPage, cursor], old) => {
        guard(() => {
          if (page !== old?.[0] && page != null) {
            pagination.setPage(page)
          }
          if (perPage !== old?.[1] && perPage != null) {
            pagination.setPerPage(perPage)
          }
        })
      },
    )
  }
}
