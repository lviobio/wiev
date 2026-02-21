import { computed, ComputedRef, shallowRef, ShallowRef } from 'vue'

// ── API response types (Laravel format, kept as-is) ────────────

// export interface PaginationMeta {
//   current_page: number
//   from: number | null
//   last_page: number
//   per_page: number
//   to: number | null
//   total: number
// }

export interface MaybePaginatedData<T> {
  data: T[]
  meta?: PaginationMetaInput
}

// ── Pagination state (discriminated union) ─────────────────────

// export interface PagePaginationInitialState {
//   type: 'page'
//   current_page?: number
//   per_page?: number
// }

export interface PagePaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PagePaginationState {
  type: 'page'
  page?: number
  per_page?: number

  meta?: PagePaginationMeta
}

export interface CursorPaginationMeta {
  next_cursor: string | null
  path: string
  per_page: number
  prev_cursor: string | null
}

export interface CursorPaginationState {
  type: 'cursor'
  cursor?: string
  per_page?: number

  meta?: CursorPaginationMeta
}

export type PaginationState = PagePaginationState | CursorPaginationState

// ── Meta input types ───────────────────────────────────────────

// export type PagePaginationMeta = Omit<PagePaginationState, 'type'>
// export type CursorPaginationMeta = Omit<CursorPaginationState, 'type'>
export type PaginationMetaInput = PagePaginationMeta | CursorPaginationMeta

function isPagePaginationMeta(meta: PaginationMetaInput): meta is PagePaginationMeta {
  return 'current_page' in meta
}

function isCursorPaginationMeta(meta: PaginationMetaInput): meta is CursorPaginationMeta {
  return 'cursor' in meta
}

function isCursorPaginationState(data?: PaginationState): data is CursorPaginationState {
  return typeof data !== 'undefined' && 'cursor' in data
}

export function castAsPage(data?: PaginationState): PagePaginationState | undefined {
  if (data?.type !== 'page') {
    return undefined
  }

  return data
}

export function castAsCursor(data?: PaginationState): CursorPaginationState | undefined {
  if (data?.type !== 'cursor') {
    return undefined
  }

  return data
}

// ── Config ─────────────────────────────────────────────────────

// export interface UsePaginationOptions {
//   defaultPerPage?: number
// }

// ── Composable return type ─────────────────────────────────────

export interface PaginationComposable {
  state: ShallowRef<PaginationState | undefined>
  refs: Required<PaginationSyncRefs>
  queryParams: ComputedRef<Record<string, unknown>>

  setPage: (page: number) => void
  setPerPage: (perPage: number) => void
  resetPage: () => void

  applyPageMeta: (meta: PagePaginationMeta) => void
  applyCursorMeta: (meta: CursorPaginationMeta) => void
  applyMeta: (meta: PaginationMetaInput | undefined) => void
}

// ── Composable ─────────────────────────────────────────────────

export const usePagination = () //options?: UsePaginationOptions
: PaginationComposable => {
  const state = shallowRef<PaginationState | undefined>(undefined)

  const queryParams = computed<Record<string, unknown>>(() => {
    const s = state.value
    if (!s) return {}
    if (s.type === 'page') return { page: s.page, per_page: s.per_page }
    if (s.type === 'cursor') return { cursor: s.cursor, per_page: s.per_page }
    return {}
  })

  function setPage(page: number) {
    const s = state.value
    if (s?.type === 'page') {
      state.value = {
        ...s,
        meta: s.meta
          ? {
              ...s.meta,
              current_page: page,
            }
          : undefined,
        page: page,
      }
    } else if (s) {
      console.warn('setPage is not supported on non-page pagination')
    }
  }

  function setPerPage(perPage: number) {
    const s = state.value
    if (!s) return
    if (s.type === 'page') {
      state.value = { ...s, per_page: perPage, page: 1 }
    } else if (s.type === 'cursor') {
      state.value = { ...s, per_page: perPage, cursor: undefined }
    }
  }

  function resetPage() {
    const s = state.value
    if (!s) return
    if (s.type === 'page') {
      state.value = { ...s, page: 1 }
    } else if (s.type === 'cursor') {
      state.value = { ...s, cursor: undefined }
    }
  }

  function applyPageMeta(data: PagePaginationMeta) {
    state.value = {
      type: 'page',
      page: data.current_page,
      per_page: data.per_page,
      meta: data,
    }
  }

  function applyCursorMeta(data: CursorPaginationMeta) {
    state.value = {
      type: 'cursor',
      cursor: isCursorPaginationState(state.value) ? state.value.cursor : undefined,
      per_page: data.per_page,
      meta: data,
    }
  }

  function applyMeta(meta: PaginationMetaInput | undefined) {
    if (!meta) {
      state.value = undefined
      return
    }

    if (isPagePaginationMeta(meta)) {
      applyPageMeta(meta)
    } else if (isCursorPaginationMeta(meta)) {
      applyCursorMeta(meta)
    }
  }

  const refs: PaginationSyncRefs = {
    page: ref(),
    perPage: ref(),
    cursor: ref(),
  }

  usePaginationSync(state, refs)

  return {
    state,
    refs,
    queryParams,
    setPage,
    setPerPage,
    resetPage,
    applyPageMeta,
    applyCursorMeta,
    applyMeta,
  }
}

// ── Sync helper ────────────────────────────────────────────────

export interface PaginationSyncRefs {
  page?: Ref<number | undefined>
  perPage?: Ref<number | undefined>
  cursor?: Ref<string | undefined>
}

/**
 * Two-way sync between pagination state and individual refs.
 *
 * When a ref changes — pagination.state is updated.
 * When pagination.state changes (e.g. after `applyMeta`) — refs are updated back.
 *
 * @example
 * const pagination = usePagination()
 * const page = ref<number | undefined>(1)
 * const perPage = ref<number | undefined>(15)
 *
 * usePaginationSync(pagination.state, { page, perPage })
 */
export function usePaginationSync(
  state: ShallowRef<PaginationState | undefined>,
  refs: PaginationSyncRefs,
): void {
  let isSyncing = false

  function guard(fn: () => void) {
    if (isSyncing) {
      console.log('!!! Prevented syncing issues')
      return
    }
    isSyncing = true
    try {
      fn()
    } finally {
      isSyncing = false
    }
  }

  const opts = { flush: 'sync' as const }

  // ── refs → state ──────────────────────────────────────────

  if (refs.page) {
    watch(
      refs.page,
      (page) =>
        guard(() => {
          state.value = {
            type: 'page',
            page,
            per_page: refs.perPage?.value ?? state.value?.per_page,
            meta: state.value?.type === 'page' ? state.value.meta : undefined,
          }
        }),
      opts,
    )
  }

  if (refs.cursor) {
    watch(
      refs.cursor,
      (cursor) =>
        guard(() => {
          state.value = {
            type: 'cursor',
            cursor,
            per_page: refs.perPage?.value ?? state.value?.per_page,
            meta: state.value?.type === 'cursor' ? state.value.meta : undefined,
          }
        }),
      opts,
    )
  }

  if (refs.perPage) {
    watch(
      refs.perPage,
      (perPage) =>
        guard(() => {
          const s = state.value
          if (!s) return

          if (s.type === 'page') {
            state.value = { ...s, per_page: perPage, page: 1 }
            if (refs.page) refs.page.value = 1
          } else if (s.type === 'cursor') {
            state.value = { ...s, per_page: perPage, cursor: undefined }
            if (refs.cursor) refs.cursor.value = undefined
          }
        }),
      opts,
    )
  }

  // ── state → refs ──────────────────────────────────────────

  watch(
    state,
    (s) =>
      guard(() => {
        if (!s) {
          if (refs.page) refs.page.value = undefined
          if (refs.cursor) refs.cursor.value = undefined
          if (refs.perPage) refs.perPage.value = undefined
          return
        }

        if (s.type === 'page') {
          if (refs.page) refs.page.value = s.page
          if (refs.perPage) refs.perPage.value = s.per_page
        } else if (s.type === 'cursor') {
          if (refs.cursor) refs.cursor.value = s.cursor
          if (refs.perPage) refs.perPage.value = s.per_page
        }
      }),
    opts,
  )
}
