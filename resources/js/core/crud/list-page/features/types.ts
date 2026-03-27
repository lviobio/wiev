import type { MaybePaginatedData, PaginationComposable } from '@/core/pagination/base'
import type { SortingComposable } from '@/core/sorting/base'
import type { Reactive, Ref } from 'vue'

// ── Feature context (provided to each feature during install) ───

export interface FeatureContext {
  /** Trigger a debounced reload (for search, filter changes) */
  loadDebounced(): void
  /** Cancel pending debounce and reload immediately (for sorting, pagination) */
  loadImmediate(): void
  /** Reset pagination to first page. No-op when pagination feature is absent. */
  resetPage(): void
  /** Register a watcher setup that runs when enableWatchers() is called. */
  onEnableWatchers(setup: () => void): void
  /** Register a handler called after each successful load. */
  onAfterLoad(handler: (result: MaybePaginatedData<unknown>) => void): void
}

// ── Feature interface ───────────────────────────────────────────

export interface ListFeature<
  Brand extends string = string,
  State extends Record<string, unknown> = Record<string, unknown>,
> {
  readonly brand: Brand
  install(ctx: FeatureContext): State
}

// ── Concrete feature type aliases ───────────────────────────────

export type PaginationFeature = ListFeature<'pagination', { pagination: PaginationComposable }>
export type SortingFeature = ListFeature<'sorting', { sorting: SortingComposable }>
export type SearchFeature = ListFeature<'search', { search: Ref<string | undefined> }>
export type FiltersFeature<F extends Record<string, unknown> = Record<string, unknown>> =
  ListFeature<'filters', { filters: Reactive<F> }>

// ── Type utilities for merging feature states ───────────────────

type UnionToIntersection<U> = (U extends unknown ? (x: U) => void : never) extends (
  x: infer I,
) => void
  ? I
  : never

type ExtractState<F> = F extends ListFeature<string, infer S> ? S : never

export type MergedState<Features extends readonly ListFeature[]> = UnionToIntersection<
  ExtractState<Features[number]>
>
