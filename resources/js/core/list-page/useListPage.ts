import AppDataTable from '@/components/AppDataTable.vue'
import { makeDataTableFiltering, type TableFiltering } from '@/components/AppDataTable/filters'
import { useNaiveUiPagination } from '@/core/pagination/naive-ui'
import { useNaiveUiSorting } from '@/core/sorting/naive-ui'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { Search24Regular } from '@vicons/fluent'
import { NFlex, NIcon, NInput } from 'naive-ui'
import { computed, defineComponent, h, markRaw, toValue, type Component } from 'vue'
import { actionsColumn, isActionsColumn, processActionsColumn } from './columns'
import type {
  ActionsColumnMarker,
  ListPageColumn,
  UseListPageOptions,
  UseListPageReturn,
} from './types'
import { LIST_PAGE_ACTIONS_SYMBOL } from './types'
import { useList } from './useList'

/**
 * Create a full list page composable with ready-to-use components.
 *
 * @example
 * ```ts
 * const ListPage = useListPage<Post, PostListFilters>({
 *   dataHandler: makeDataHandlerFromRepository(repository),
 *   filtersSchema: postListFiltersSchema,
 *   filters: defineFilters(postListFiltersSchema, { ... }),
 *   columns: [...],
 *   search: { placeholder: 'Search' },
 *   table: { size: 'small', striped: true, remote: true },
 * })
 *
 * // Template:
 * // <ListPage.Component />          — full page
 * // <ListPage.Partial.Table />      — table only
 * // <ListPage.Partial.Search />     — search only
 * // <ListPage.Partial.Wrapper />    — wrapper with slots
 * ```
 */
export function useListPage<T, F extends Record<string, unknown>>(
  options: UseListPageOptions<T, F>,
): UseListPageReturn<T, F> {
  const {
    dataHandler,
    filtersSchema,
    filters: filterItems,
    search: searchConfig,
    table: tableConfig,
  } = options

  // ── Core list state ───────────────────────────────────────────

  const list = useList<T, F>({
    filters: createEmptyObjectFromSchema(filtersSchema) as F,
    debounceMs: options.debounceMs,
    loader: async ({ filters, pagination, sorting, search, signal }) => {
      return dataHandler({
        filters: toRaw(filters) as F,
        pagination,
        sorting,
        search: search.value,
        signal,
      })
    },
  })

  const { items, loading, params, load, enableWatchers } = list

  // ── Naive UI adapters ─────────────────────────────────────────

  const dataTablePagination = useNaiveUiPagination(params.pagination)
  const { getSortOrder, onUpdateSorter } = useNaiveUiSorting(params.sorting)

  // ── Filter integration ────────────────────────────────────────

  const filtering: TableFiltering | undefined = filterItems?.length
    ? makeDataTableFiltering(ref(params.filters) as any, filterItems as any)
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

        const actionsRender = processActionsColumn<T>(finalActions, load)
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

  // ── Components ────────────────────────────────────────────────

  const SearchComponent = markRaw(
    defineComponent({
      name: 'ListPageSearch',
      setup() {
        const placeholder = typeof searchConfig === 'object' ? searchConfig?.placeholder : 'Search'

        return () => {
          if (searchConfig === false) return null

          return h(
            NInput,
            {
              value: params.search.value || '',
              'onUpdate:value': (val: string) => {
                params.search.value = val !== '' ? val : undefined
              },
              placeholder,
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
              pagination: dataTablePagination.value,
              filtering,
              remote: tableConfig?.remote ?? true,
              striped: tableConfig?.striped ?? true,
              'onUpdate:sorter': onUpdateSorter,
            },
            {
              header: () => (searchConfig !== false ? h(SearchComponent) : null),
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
      filters: params.filters,
      search: params.search,
      pagination: params.pagination,
      sorting: params.sorting,
    },

    actions: {
      load,
      enableWatchers,
    },

    params,
  }
}
