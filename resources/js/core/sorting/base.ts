import { computed, ComputedRef, Ref, ref } from 'vue'

// ── Types ──────────────────────────────────────────────────────

export type SortOrder = 'asc' | 'desc'

export interface SortField {
  field: string
  order: SortOrder
}

export type SortingParamsSerializer = (state: SortField[]) => Record<string, unknown>

// ── Composable return type ─────────────────────────────────────

export interface SortingComposable {
  state: Ref<SortField[]>
  params: ComputedRef<Record<string, unknown>>

  /** Replace all sorting with a single field */
  setSort: (field: string, order: SortOrder) => void
  /** Cycle through: none → asc → desc → none */
  toggleSort: (field: string) => void
  /** Add or update a field in multi-sort */
  addSort: (field: string, order: SortOrder) => void
  /** Remove a field from sorting */
  removeSort: (field: string) => void
  /** Clear all sorting */
  resetSort: () => void
}

// ── Serializers ────────────────────────────────────────────────

/**
 * Spatie-style serializer: produces `{ sort: 'title,-created_at' }`.
 * Minus prefix means descending order.
 */
export function spatieSortSerializer(state: SortField[]): Record<string, unknown> {
  if (state.length === 0) return {}

  const sort = state.map((s) => (s.order === 'desc' ? `-${s.field}` : s.field)).join(',')

  return { sort }
}

// ── Composable ─────────────────────────────────────────────────

export interface UseSortingOptions {
  serializer?: SortingParamsSerializer
}

export const useSorting = (options?: UseSortingOptions): SortingComposable => {
  const state = ref<SortField[]>([])
  const serializer = options?.serializer ?? spatieSortSerializer

  const params = computed<Record<string, unknown>>(() => serializer(state.value))

  function setSort(field: string, order: SortOrder) {
    state.value = [{ field, order }]
  }

  function toggleSort(field: string) {
    const existing = state.value.find((s) => s.field === field)

    if (!existing) {
      state.value = [{ field, order: 'asc' }]
    } else if (existing.order === 'asc') {
      state.value = state.value.map((s) =>
        s.field === field ? { ...s, order: 'desc' as const } : s,
      )
    } else {
      state.value = state.value.filter((s) => s.field !== field)
    }
  }

  function addSort(field: string, order: SortOrder) {
    const filtered = state.value.filter((s) => s.field !== field)
    state.value = [...filtered, { field, order }]
  }

  function removeSort(field: string) {
    state.value = state.value.filter((s) => s.field !== field)
  }

  function resetSort() {
    state.value = []
  }

  return {
    state,
    params,
    setSort,
    toggleSort,
    addSort,
    removeSort,
    resetSort,
  }
}
