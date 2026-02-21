import { castAsCursor, castAsPage, type PaginationComposable } from '@/core/pagination/base'
import type { SortingComposable } from '@/core/sorting/base'
import { toRaw, watch, type Reactive, type Ref } from 'vue'
import { deepAssign, type ContextSyncChannel } from './useListContextSync'

// ── Filters ──────────────────────────────────────────────────────

export function createFiltersSyncChannel<F extends Record<string, any>>(
  filters: Reactive<F>,
): ContextSyncChannel {
  return {
    install({ context, guarded, register }) {
      const initial = context.value.filters
      if (initial && typeof initial === 'object') {
        deepAssign(filters, toRaw(initial))
      }

      // filters -> context.filters
      register(
        watch(
          filters,
          (newVal) => {
            const raw = toRaw(newVal)
            const current = toRaw(context.value.filters ?? {})
            if (JSON.stringify(raw) === JSON.stringify(current)) return
            guarded(() => {
              if (!context.value.filters) context.value.filters = {}
              deepAssign(context.value.filters as Record<string, any>, raw)
            })
          },
          { deep: true },
        ),
      )

      // context.filters -> filters
      register(
        watch(
          () => context.value.filters,
          (newVal) => {
            if (!newVal || typeof newVal !== 'object') return
            const raw = toRaw(newVal)
            if (JSON.stringify(raw) === JSON.stringify(toRaw(filters))) return
            deepAssign(filters as Record<string, any>, raw)
          },
          { deep: true },
        ),
      )
    },
  }
}

// ── Search ───────────────────────────────────────────────────────

export function createSearchSyncChannel(search: Ref<string | undefined>): ContextSyncChannel {
  return {
    install({ context, guarded, register }) {
      search.value = context.value.search

      // search -> context.search
      register(
        watch(search, (newVal) => {
          if (newVal === context.value.search) return
          guarded(() => {
            context.value.search = newVal
          })
        }),
      )

      // context.search -> search
      register(
        watch(
          () => context.value.search,
          (newVal) => {
            if (newVal === search.value) return
            search.value = newVal
          },
        ),
      )
    },
  }
}

// ── Pagination ───────────────────────────────────────────────────

export function createPaginationSyncChannel(pagination: PaginationComposable): ContextSyncChannel {
  return {
    install({ context, guarded, register }) {
      // Direct write: setPage/setPerPage are no-ops before first applyMeta,
      // so on init we write what the context already has.
      if (typeof context.value.page === 'number') {
        pagination.setPage(context.value.page)
      } else if (typeof context.value.cursor === 'string') {
        pagination.setCursor(context.value.cursor)
      }

      if (pagination.state.value && typeof context.value.per_page === 'number') {
        pagination.setPerPage(context.value.per_page)
      }

      // pagination.state -> context
      register(
        watch(pagination.state, (state) => {
          guarded(() => {
            context.value.page = castAsPage(state)?.page
            context.value.cursor = castAsCursor(state)?.cursor
            context.value.per_page = state?.per_page
          })
        }),
      )

      // context -> pagination
      register(
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
    },
  }
}

// ── Sorting ──────────────────────────────────────────────────────

export function createSortingSyncChannel(sorting: SortingComposable): ContextSyncChannel {
  return {
    install({ context, guarded, register }) {
      if (context.value.sort?.length) {
        sorting.state.value = [...context.value.sort]
      }

      // sorting.state -> context.sort
      register(
        watch(sorting.state, (newVal) => {
          const raw = toRaw(newVal)
          if (JSON.stringify(raw) === JSON.stringify(toRaw(context.value.sort ?? []))) return
          guarded(() => {
            context.value.sort = [...raw]
          })
        }),
      )

      // context.sort -> sorting.state
      register(
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
    },
  }
}
