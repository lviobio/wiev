import { Indicator } from '@/components/AppDataTable/filters/base'
import { cloneDeep, get, set } from 'lodash'

// Re-export types that are needed for abstract usage
export interface ActiveFilter {
  key: string
  indicator: Indicator
  remove: () => void
}

// Minimal filter interface for abstract usage
export interface MinimalTableFilter {
  key: string
  getIndicator: (state: any) => Indicator[]
}

export type FiltersState = Record<string, unknown>

export interface UseAbstractTableFiltersOptions<
  FS extends FiltersState,
  F extends MinimalTableFilter = MinimalTableFilter,
> {
  filtering: Ref<{ state: Ref<FS>; items: Array<F> } | undefined>
  getFilterRef: <K extends keyof FS & string>(filter: F, state: Ref<FS>) => Ref<FS[K]>
  getRemovedFilterValue: <K extends keyof FS & string>(filter: F, state: FS) => FS[K]
}

export function useAbstractTableFilters<FS extends FiltersState>({
  filtering,
  getFilterRef,
  getRemovedFilterValue,
}: UseAbstractTableFiltersOptions<FS>) {
  const activeFilterKeys = computed(() => {
    const result: string[] = []

    filtering.value?.items?.forEach((filter) => {
      const currentValue = getFilterRef(filter, filtering.value!.state).value
      const removedValue = getRemovedFilterValue(filter, filtering.value!.state.value)

      if (JSON.stringify(currentValue) !== JSON.stringify(removedValue)) {
        result.push(filter.key)
      }
    })

    return result
  })

  const activeFilters = computed<ActiveFilter[]>(() => {
    const result: ActiveFilter[] = []

    filtering.value?.items?.forEach((filter) => {
      if (!activeFilterKeys.value.includes(filter.key)) {
        return
      }

      const filterRef = getFilterRef(filter, filtering.value!.state)
      const indicators = filter.getIndicator(filterRef.value)

      // Create separate ActiveFilter for each indicator
      indicators.forEach((indicator) => {
        result.push({
          key: filter.key,
          indicator,
          remove: () => {
            if (indicator.removeField) {
              // Remove specific field using dot-notation
              const currentValue = filterRef.value

              // Check if current value is an object (not a primitive)
              if (typeof currentValue === 'object' && currentValue !== null) {
                const removedFilterValue = getRemovedFilterValue(
                  filter,
                  filtering.value!.state.value,
                )
                const removedFieldValue = get(removedFilterValue, indicator.removeField)

                // Deep clone current filter value and set removed value for specific field
                const newValue = cloneDeep(currentValue)
                set(newValue, indicator.removeField, removedFieldValue)
                filterRef.value = newValue as any
              } else {
                // For primitive values, remove entire filter
                filterRef.value = getRemovedFilterValue(filter, filtering.value!.state.value)
              }
            } else {
              // Remove entire filter
              filterRef.value = getRemovedFilterValue(filter, filtering.value!.state.value)
            }
          },
        })
      })
    })

    return result
  })

  return {
    activeFilterKeys,
    activeFilters,
  }
}
