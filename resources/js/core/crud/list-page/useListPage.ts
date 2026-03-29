import AppDataTable from '@/components/AppDataTable.vue'
import { makeDataTableFiltering, type TableFiltering } from '@/components/AppDataTable/filters'
import { createIndicator } from '@/components/AppDataTable/filters/base'
import type { ActiveFilter } from '@/components/AppDataTable/useAbstractTableFilters'
import { useListContextSync } from '@/core/list-context/useListContextSync'
import { useNaiveUiCursorPagination, useNaiveUiPagePagination } from '@/core/pagination/naive-ui'
import { useNaiveUiSorting } from '@/core/sorting/naive-ui'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { Search24Regular } from '@vicons/fluent'
import { NFlex, NIcon, NInput } from 'naive-ui'
import { type Component, computed, defineComponent, h, markRaw, toValue } from 'vue'
import { z } from 'zod'
import { actionsColumn, isActionsColumn, processActionsColumn } from './columns'
import {
  hasPagination,
  hasSearch,
  hasSorting,
  withFilters,
  withPagination,
  withSearch,
  withSorting,
} from './features'
import { defineFilters } from './filters'
import {
  ActionsColumnMarker,
  DefaultListFeaturesState,
  LIST_PAGE_ACTIONS_SYMBOL,
  ListComposables,
  ListContextSymbol,
  ListPageColumn,
  UseListPageOptions,
  UseListPageReturn,
} from './types'
import { useList } from './useList'

/**
 * Create a full list page composable with ready-to-use components.
 *
 * Types are inferred automatically:
 * - `T` (row type) from `dataHandler`'s return type
 * - `F` (filters) from `filtersSchema` via `z.infer`
 *
 * @example
 * ```ts
 * const ListPage = useListPage({
 *   dataHandler: makeDataHandlerFromRepositoryAdapter(repository.list.bind(repository)),
 *   filtersSchema: postListFiltersSchema,
 *   columns: [...],
 *   actions: [...],
 *   search: { placeholder: 'Search' },
 * })
 *
 * // Template:
 * // <ListPage.Component />          — full page
 * // <ListPage.Partial.Table />      — table only
 * // <ListPage.Partial.Search />     — search only
 * // <ListPage.Partial.Wrapper />    — wrapper with slots
 * ```
 */
export function useListPage<
  T extends Record<string, any>,
  FS extends z.ZodObject,
  FeaturesState extends Record<string, unknown> = DefaultListFeaturesState<z.infer<FS>>,
>(options: UseListPageOptions<T, FS, FeaturesState>): UseListPageReturn<T, z.infer<FS>> {
  type FST = z.infer<FS>
  const {
    dataHandler,
    filtersSchema,
    filters: filterItems,
    search: searchConfig,
    table: tableConfig,
    contextSymbol = ListContextSymbol,
  } = options

  const context = contextSymbol ? inject(contextSymbol, undefined) : undefined

  const composables: ListComposables = {
    router: useRouter(),
    message: useMessage(),
    dialog: useDialog(),
  }

  const filters = reactive(createEmptyObjectFromSchema(filtersSchema) as FST)

  // ── Core list state ───────────────────────────────────────────

  const list = useList({
    features: options.features ?? [
      withPagination(),
      withSorting(),
      withSearch(),
      withFilters(filters),
    ],
    debounceMs: options.debounceMs,
    loader: async (params) => {
      return dataHandler({
        features: params.features as unknown as FeaturesState,
        signal: params.signal,
      })
    },
  })

  const { items, loading, load, features } = list

  const pagination = hasPagination(features) ? features.pagination : undefined
  const sorting = hasSorting(features) ? features.sorting : undefined
  const search = hasSearch(features) ? features.search : undefined

  // ── Context sync ────────────────────────────────────────────────

  if (context) {
    useListContextSync(context.get(), {
      filters,
      pagination,
      sorting,
      search,
    })
  }

  // ── Naive UI adapters ─────────────────────────────────────────

  const dataTablePagePagination = pagination ? useNaiveUiPagePagination(pagination) : ref()
  const dataTableCursorPagination = pagination ? useNaiveUiCursorPagination(pagination) : ref()
  const { getSortOrder, onUpdateSorter } = sorting
    ? useNaiveUiSorting(sorting)
    : { getSortOrder: () => false, onUpdateSorter: () => {} }

  // ── Filter integration ────────────────────────────────────────

  const resolvedFilterItems = filterItems ?? defineFilters(filtersSchema)

  const filtering: TableFiltering | undefined = resolvedFilterItems.length
    ? makeDataTableFiltering(toRef(filters) as any, resolvedFilterItems as any)
    : undefined

  // ── Column processing ─────────────────────────────────────────

  /**
   * Process raw columns:
   * 1. Auto-append actionsColumn() if `actions` provided but no marker in columns
   * 2. Inject sortOrder for columns with `sorter: true`
   * 3. Inject render function into actionsColumn marker from `options.actions`
   * 4. Auto-add `filter: true` for columns matching a filter definition
   */
  const processedColumns = computed(() => {
    const rawColumns = [...(toValue(options.columns) ?? [])]

    // Auto-append actions column if actions are provided but no marker exists
    if (options.actions?.length && !rawColumns.some(isActionsColumn)) {
      rawColumns.push(actionsColumn())
    }

    return rawColumns.map((col: ListPageColumn<T>) => {
      const processed = { ...col } as any

      // Inject sortOrder for sortable columns
      if ('sorter' in processed && processed.sorter && 'key' in processed) {
        processed.sortOrder = getSortOrder(String(processed.key))
      }

      // Inject actions render function into the marker column
      if (isActionsColumn(col) && options.actions?.length) {
        const marker = (col as any)[LIST_PAGE_ACTIONS_SYMBOL] as ActionsColumnMarker
        let finalActions = options.actions

        if (typeof marker === 'function') {
          const result = marker({ actions: options.actions })
          finalActions = result.actions as typeof finalActions
          if (result.title) processed.title = result.title
          if (result.width) processed.width = result.width
        }

        const actionsRender = processActionsColumn<T>(composables, finalActions, load)
        processed.render = actionsRender.render
        delete processed[LIST_PAGE_ACTIONS_SYMBOL]
      }

      // Auto-add filter: true for columns that have a matching filter
      if (filtering && 'key' in processed) {
        const hasFilter = filtering.items.some((f) => f.key === processed.key)
        if (hasFilter && processed.filter === undefined) {
          processed.filter = true
        }
      }

      return processed
    })
  })

  // ── Search active filter indicator ─────────────────────────────

  const searchActiveFilters = computed<ActiveFilter[]>(() => {
    if (!search?.value) return []

    return [
      {
        key: 'search',
        indicator: createIndicator(`Search: ${search.value}`),
        remove: () => {
          search.value = undefined
        },
      },
    ]
  })

  // ── Components ────────────────────────────────────────────────

  const SearchComponent = markRaw(
    defineComponent({
      name: 'ListPageSearch',
      setup() {
        const { placeholder = () => 'Search' } = searchConfig || {}

        return () => {
          if (searchConfig === false) return null

          return h(
            NInput,
            {
              value: search?.value || '',
              'onUpdate:value': (val: string) => {
                if (search) search.value = val !== '' ? val : undefined
              },
              placeholder: toValue(placeholder),
              clearable: true,
            },
            {
              prefix: () => h(NIcon, null, { default: () => h(Search24Regular) }),
            },
          )
        }
      },
    }),
  ) as Component

  const TableComponent = markRaw(
    defineComponent({
      name: 'ListPageTable',
      props: {
        columns: { type: Array, default: undefined },
      },
      setup(props) {
        return () => {
          const cols = (props.columns as any) ?? processedColumns.value

          return h(
            AppDataTable as any,
            {
              columns: cols,
              data: items.value,
              loading: loading.value,
              loader: load,
              size: tableConfig?.size ?? 'small',
              pagination: dataTablePagePagination.value || dataTableCursorPagination.value,
              filtering,
              extraActiveFilters: searchActiveFilters.value,
              remote: tableConfig?.remote ?? true,
              striped: tableConfig?.striped ?? true,
              'onUpdate:sorter': onUpdateSorter,
            },
            {
              header: () => h('div', [searchConfig !== false ? h(SearchComponent) : null]),
            },
          )
        }
      },
    }),
  ) as Component

  const WrapperComponent = markRaw(
    defineComponent({
      name: 'ListPageWrapper',
      setup(_, { slots }) {
        return () => h(NFlex, null, { default: () => slots.default?.() })
      },
    }),
  ) as Component

  const FullComponent = markRaw(
    defineComponent({
      name: 'ListPageComponent',
      setup() {
        return () =>
          h(WrapperComponent, null, {
            default: () => h(TableComponent),
          })
      },
    }),
  ) as Component

  // ── Return ────────────────────────────────────────────────────

  return {
    Component: FullComponent,

    Partial: {
      Wrapper: WrapperComponent,
      Table: TableComponent,
      Search: SearchComponent,
    },

    state: {
      items,
      loading,
      filters,
      search,
      pagination,
      sorting,
    },

    actions: {
      load,
    },
  }
}
