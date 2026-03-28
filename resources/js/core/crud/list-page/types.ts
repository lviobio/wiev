import type { TableFilter } from '@/components/AppDataTable/filters/base'
import type { MaybePaginatedData, PaginationComposable } from '@/core/pagination/base'
import type { SortingComposable } from '@/core/sorting/base'
import type { DialogApi, MessageApi } from 'naive-ui'
import type { Component, MaybeRefOrGetter, Reactive, Ref, VNodeChild } from 'vue'
import type { Router } from 'vue-router'
import { z, type ZodObject } from 'zod'

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

export type DataLoaderFn<T, F extends Record<string, unknown>> = (
  params: DataLoaderParams<F>,
) => Promise<MaybePaginatedData<T>>

export type DataLoader<T, F extends Record<string, unknown>> = DataLoaderFn<T, F> & {
  /** @internal Phantom brand for type inference. Never set at runtime. */
  readonly _type?: T
}

export interface DataLoaderParams<F extends Record<string, unknown>> {
  filters: F
  pagination: PaginationComposable
  sorting: SortingComposable
  search: string | undefined
  signal: AbortSignal
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

export type ListPageColumn<T> = Column<T> | (Column<T> & ActionsColumnDef)

// ── Filter Override ─────────────────────────────────────────────

export interface FilterOverride {
  type?: 'text' | 'daterange' | 'select'
  title?: string
  placeholder?: string
  options?: Array<{ label: string; value: string }>
}

// ── Options & Return ────────────────────────────────────────────

export const ListContextSymbol = Symbol()

export interface UseListPageOptions<T extends Record<string, any>, FS extends ZodObject> {
  dataHandler: DataLoader<T, z.infer<FS>>
  filtersSchema: FS
  filters?: TableFilter[]
  columns?: MaybeRefOrGetter<ListPageColumn<NoInfer<T>>[]>
  actions?: ActionDef<T>[]
  search?: { placeholder?: MaybeRefOrGetter<string> } | false
  table?: Partial<{
    size: 'small' | 'medium' | 'large'
    striped: boolean
    remote: boolean
  }>
  debounceMs?: number
  contextSymbol?: symbol | false
}

export interface UseListPageState<T, F extends Record<string, unknown>> {
  items: ShallowRef<T[]>
  loading: Ref<boolean>
  filters: Reactive<F>
  search: Ref<string | undefined>
  pagination: PaginationComposable
  sorting: SortingComposable
}

export interface UseListPageActions {
  load: () => Promise<void>
}

/**
 * Return value of `useListPage`.
 *
 * Usage:
 * ```vue
 * const ListPage = useListPage({ ... })
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
