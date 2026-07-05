import type { TableFilter } from '@/components/AppDataTable/filters/base'
import type { ListFeature } from '@/core/crud/list-page/features/types'
import type { ListContextConstraint } from '@/core/list-context/useListContextSync'
import type { MaybePaginatedData, PaginationComposable } from '@/core/pagination/base'
import type { SortingComposable } from '@/core/sorting/base'
import type { DialogApi, MessageApi } from 'naive-ui'
import type { Component, InjectionKey, MaybeRefOrGetter, Reactive, Ref, VNodeChild } from 'vue'
import type { Router } from 'vue-router'
import { type ZodObject } from 'zod'

// ── Composables ─────────────────────────────────────────────────

export interface ListComposables {
  router: Router
  message: MessageApi
  dialog: DialogApi
}

// ── Abstract Column ─────────────────────────────────────────────

export interface Column<T> {
  key: Extract<keyof T, string> | (string & {})
  title?: string | (() => VNodeChild)
  width?: number | string
  sorter?: boolean
  filter?: boolean
  render?: (rowData: T, rowIndex: number) => VNodeChild
  ellipsis?: boolean | Record<string, unknown>
}

// ── Data Loading ────────────────────────────────────────────────

export interface DataLoaderParams<FS = Record<string, unknown>> {
  features: FS
  signal: AbortSignal
}

export type DataLoader<T, FS = Record<string, unknown>> = (
  params: DataLoaderParams<FS>,
) => Promise<MaybePaginatedData<T>>

/** Features state when default features (pagination, sorting, search, filters) are used. */
export type DefaultListFeaturesState<F extends Record<string, unknown>> = {
  pagination: PaginationComposable
  sorting: SortingComposable
  search: Ref<string | undefined>
  filters: Reactive<F>
}

// ── Column Actions ──────────────────────────────────────────────

export const LIST_PAGE_ACTIONS_SYMBOL = Symbol('listPageActions')

export interface OpenActionDef<T> {
  type: 'open'
  to: (row: T) => any // RouteLocationRaw
  label?: string
  windowed?: boolean | ((row: T) => { title: string })
}

export interface DeleteActionDef<T> {
  type: 'delete'
  handler: (row: T) => Promise<unknown>
  confirm?: string | ((row: T) => string)
  success?: string | ((row: T) => string)
  label?: string
}

export interface ActionGroupDef<T> {
  type: 'group'
  actions: (OpenActionDef<T> | DeleteActionDef<T>)[]
  label?: string
}

export type ActionDef<T> = OpenActionDef<T> | DeleteActionDef<T> | ActionGroupDef<T>

/**
 * Callback for customizing the actions column.
 * Receives the actions defined in `useListPage`'s `actions` option
 * and returns the final actions + optional column overrides.
 */
export type ActionsColumnCallback = (context: { actions: ActionDef<any>[] }) => {
  actions: ActionDef<any>[]
  width?: number
  title?: string
}

export type ActionsColumnMarker = true | ActionsColumnCallback

export interface ActionsColumnDef {
  [LIST_PAGE_ACTIONS_SYMBOL]: ActionsColumnMarker
}

// ── List Page Column ────────────────────────────────────────────

/** A column whose `key` is a field of `T` — the cell is rendered from row data. */
export type DataColumn<T> = Column<T> & { key: Extract<keyof T, string> }

/** A column that renders itself — `key` may be any string (e.g. computed columns). */
export type RenderedColumn<T> = Column<T> & { render: NonNullable<Column<T>['render']> }

/**
 * Column accepted by `useListPage`: either its `key` exists in `T`,
 * or it must provide a `render` function (nothing to auto-render otherwise),
 * or it is an actions column placeholder.
 *
 * `DataColumn` is deliberately the LAST constituent: when none match,
 * TypeScript elaborates the error against the last one, so the final
 * message is about the invalid `key` — the most likely mistake.
 */
export type ListPageColumn<T> =
  | (Column<T> & ActionsColumnDef)
  | RenderedColumn<T>
  | DataColumn<T>

// ── Column Helpers ──────────────────────────────────────────────

export interface LinkColumnOptions<T> {
  width?: number
  title?: string
  sorter?: boolean
  to: (row: T) => any // RouteLocationRaw
  windowed?: boolean | ((row: T) => { title: string })
}

export interface DateColumnOptions {
  width?: number
  title?: string
  sorter?: boolean
}

/**
 * Column helper functions with the row type `T` already bound.
 *
 * Passed to the `columns` factory callback of `useListPage` so that helper
 * calls are fully typed (`key` is checked against `keyof T`, `row` callbacks
 * receive `T`) without explicit type arguments. Inlining generic helpers like
 * `linkColumn(...)` directly into the options object would collapse their
 * inference to `Record<string, any>` — TypeScript resolves nested generic
 * calls before the outer `T` is fixed.
 */
export interface ColumnHelpers<T extends Record<string, any>> {
  /**
   * Plain data column. Prefer this over an object literal — an invalid key
   * produces a short, precise error at the call site instead of a long
   * union-assignability chain on the whole `columns` factory.
   * `title` defaults to a humanized key.
   */
  column(key: Extract<keyof T, string>, options?: Omit<Column<T>, 'key'>): DataColumn<T>
  linkColumn(key: Extract<keyof T, string>, options: LinkColumnOptions<T>): RenderedColumn<T>
  dateColumn(key: Extract<keyof T, string>, options?: DateColumnOptions): RenderedColumn<T>
  actionsColumn(
    optionsOrCallback?: { width?: number; title?: string } | ActionsColumnCallback,
  ): ListPageColumn<T>
}

/** Persistent bag passed as the third argument to the `columns` factory. */
export type ColumnsStorage = Record<string, unknown>

export type ColumnsFactory<
  T extends Record<string, any>,
  S extends Record<string, unknown> = ColumnsStorage,
> = (
  helpers: ColumnHelpers<T>,
  /** The whole last successful server response (`{ data, meta }`); empty `data` before the first load. */
  data: MaybePaginatedData<T>,
  storage: S,
) => ListPageColumn<T>[]

// ── Filter Override ─────────────────────────────────────────────

export interface FilterOverride {
  type?: 'text' | 'daterange' | 'select'
  title?: string
  placeholder?: string
  options?: Array<{ label: string; value: string }>
}

// ── Options & Return ────────────────────────────────────────────

export interface ListContextProvider {
  get(): Ref<ListContextConstraint<Record<string, unknown>>>
}

export const ListContextSymbol: InjectionKey<ListContextProvider> = Symbol('ListContext')

export interface UseListPageOptions<
  T extends Record<string, any>,
  FS extends ZodObject,
  S extends Record<string, unknown> = ColumnsStorage,
> {
  filtersSchema?: FS
  filters?: TableFilter[]
  /**
   * Initial value of the persistent storage bag. Its type also types the
   * `storage` argument passed to the `columns` factory. The object lives for
   * the whole lifetime of the list, so data written to it survives reloads.
   */
  storage?: S
  /**
   * Factory returning the column list. Receives typed column helpers
   * (`linkColumn`, `dateColumn`, `actionsColumn`) with the row type bound,
   * a `data` argument holding the whole last server response (`{ data, meta }`),
   * and a persistent `storage` bag for data that must survive reloads
   * (e.g. column config that only arrives in some responses).
   *
   * Deliberately NOT a plain array: a union type here (`array | factory`)
   * makes TypeScript report errors against the whole option instead of the
   * exact invalid column. Static columns are simply `columns: () => [...]`.
   * The factory runs inside a computed, so reactive values used in it are
   * tracked automatically.
   */
  columns?: ColumnsFactory<T, S>
  actions?: ActionDef<T>[]
  search?: { placeholder?: MaybeRefOrGetter<string> } | false
  table?: Partial<{
    size: 'small' | 'medium' | 'large'
    striped: boolean
    remote: boolean
  }>
  features?: ListFeature[]
  debounceMs?: number
  contextSymbol?: InjectionKey<ListContextProvider> | false
  /**
   * Perform the first load automatically when the composable is mounted.
   * Defaults to `true`. Set to `false` for headless/custom-layout usage where
   * the consumer controls the initial load via `actions.load()`.
   */
  autoLoad?: boolean
}

export interface UseListPageState<T, F extends Record<string, unknown>> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  filters?: Reactive<F>
  search?: Ref<string | undefined>
  pagination?: PaginationComposable
  sorting?: SortingComposable
}

export interface UseListPageActions {
  load: () => Promise<void>
}

/**
 * Return value of `useListPage`.
 *
 * Usage:
 * ```vue
 * const ListPage = useListPage(dataHandler, { ... })
 *
 * <ListPage.Component />              <!-- full page -->
 * <ListPage.Partial.Wrapper />        <!-- wrapper only -->
 * <ListPage.Partial.Table />          <!-- table only -->
 * <ListPage.Partial.Search />         <!-- search input only -->
 *
 * ListPage.state.items                // reactive data
 * ListPage.actions.load()             // reload
 * ```
 */
export interface UseListPageReturn<T, F extends Record<string, unknown>> {
  /** Full page component (search + table + pagination) */
  Component: Component

  /** Individual sub-components for custom layouts */
  Partial: {
    /** Wrapper container (NFlex) with default slot */
    Wrapper: Component
    /** Table component with bound data, filters, pagination */
    Table: Component
    /** Search input component */
    Search: Component
  }

  state: UseListPageState<T, F>
  actions: UseListPageActions
}
