import type { MaybePaginatedData, PaginationComposable } from '@/core/pagination/base'
import type { SortingComposable } from '@/core/sorting/base'
import type { Reactive, Ref } from 'vue'

// ── Feature context (provided to each feature during install) ───

export interface FeatureContext {
  /** Trigger a debounced reload (for search, filter changes) */
  loadDebounced(): void
  /** Cancel pending debounce and reload immediately (for sorting, pagination) */
  loadImmediate(): void
  /** Register a handler called after each successful load. */
  onAfterLoad(handler: (result: MaybePaginatedData<unknown>) => void): void
  /** Publish a capability for other features to consume. */
  provide(key: symbol, value: unknown): void
  /** Retrieve a capability published by another feature. Safe to call at runtime (inside watchers). */
  resolve<T = unknown>(key: symbol): T | undefined
}

// ── Render slots (extension points in useListPage render tree) ──

/**
 * Named extension points in the render tree produced by `useListPage`.
 * A feature can contribute props to any of these via `install().contributions`.
 */
export type ListPageSlot = 'table' | 'search' | 'wrapper'

export type SlotContributions = Partial<Record<ListPageSlot, Record<string, unknown>>>

/** Result of merging all features' contributions, shallow-merged per slot. */
export type MergedContributions = Record<ListPageSlot, Record<string, unknown>>

// ── Feature install result ──────────────────────────────────────

export interface FeatureInstallResult<
  State extends Record<string | symbol, unknown> = Record<string | symbol, unknown>,
> {
  state: State
  contributions?: SlotContributions
}

// ── Feature interface ───────────────────────────────────────────

export interface ListFeature<
  Brand extends string = string,
  State extends Record<string | symbol, unknown> = Record<string | symbol, unknown>,
> {
  readonly brand: Brand
  /**
   * Install order (ascending). Lower runs first.
   * Built-in features are spaced by 1000 to leave room for user features:
   * pagination=1000, sorting=2000, search=3000, filters=4000.
   */
  readonly priority: number
  install(ctx: FeatureContext): FeatureInstallResult<State>
}

// ── Concrete feature type aliases ───────────────────────────────

export type PaginationFeature = ListFeature<'pagination', { pagination: PaginationComposable }>
export type SortingFeature = ListFeature<'sorting', { sorting: SortingComposable }>
export type SearchFeature = ListFeature<'search', { search: Ref<string | undefined> }>
export type FiltersFeature<
  F extends Record<string, unknown> = Record<string, unknown>,
> = ListFeature<'filters', { filters: Reactive<F> }>

// ── Feature type guards ──────────────────────────────────────────

export function hasPagination(
  features: Record<string, unknown>,
): features is { pagination: PaginationComposable } {
  return 'pagination' in features
}

export function hasSorting(
  features: Record<string, unknown>,
): features is { sorting: SortingComposable } {
  return 'sorting' in features
}

export function hasSearch(
  features: Record<string, unknown>,
): features is { search: Ref<string | undefined> } {
  return 'search' in features
}

export function hasFilters<F extends Record<string, unknown> = Record<string, unknown>>(
  features: Record<string, unknown>,
): features is { filters: Reactive<F> } {
  return 'filters' in features
}

// ── Shared feature keys ─────────────────────────────────────────

/** Key used by pagination to publish its resetPage function. */
export const PaginationResetPageKey: unique symbol = Symbol('paginationResetPage')

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
