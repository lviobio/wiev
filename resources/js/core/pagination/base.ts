import { pick } from 'lodash'
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
const pagePaginationMetaKeys: (keyof PagePaginationMeta)[] = [
  'current_page',
  'from',
  'last_page',
  'per_page',
  'to',
  'total',
]

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
const cursorPaginationMetaKeys: (keyof CursorPaginationMeta)[] = [
  'next_cursor',
  'path',
  'per_page',
  'prev_cursor',
]

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
  params: ComputedRef<Record<string, unknown>>

  setPage: (page: number) => void
  setCursor: (cursor: string) => void
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

  const params = computed<Record<string, unknown>>(() => {
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

  function setCursor(cursor: string) {
    const s = state.value
    if (s?.type === 'cursor') {
      state.value = { ...s, cursor }
    } else if (s) {
      console.warn('setCursor is not supported on non-cursor pagination')
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
      meta: pick(data, pagePaginationMetaKeys),
    }
  }

  function applyCursorMeta(data: CursorPaginationMeta) {
    state.value = {
      type: 'cursor',
      cursor: isCursorPaginationState(state.value) ? state.value.cursor : undefined,
      per_page: data.per_page,
      meta: pick(data, cursorPaginationMetaKeys),
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

  return {
    state,
    params,
    setPage,
    setCursor,
    setPerPage,
    resetPage,
    applyPageMeta,
    applyCursorMeta,
    applyMeta,
  }
}
