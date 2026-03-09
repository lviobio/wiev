import {
  createIndicator,
  resolveIndicators,
  type TableFilter,
} from '@/components/AppDataTable/filters/base'
import { NSelect, type SelectOption } from 'naive-ui'

export interface UseSelectFilterOptions {
  options?: SelectOption[]
  placeholder?: string
  indicateUsing?: (
    state: string | null,
    indicators: Array<string | import('./base').Indicator>,
  ) => undefined | Array<string | import('./base').Indicator>
}

export function useSelectFilter<K extends string>(
  key: K,
  title: string,
  filterOptions: UseSelectFilterOptions = {},
): TableFilter<K, string | null> {
  const { options: selectOptions = [], placeholder, indicateUsing } = filterOptions

  const defaultIndicateUsing =
    indicateUsing ??
    ((state) => {
      if (!state) return []
      const option = selectOptions.find((o) => o.value === state)
      const label = option?.label ?? state
      return [createIndicator(`${title}: ${label}`)]
    })

  return {
    key,
    title,

    getIndicator(state) {
      return resolveIndicators(state, defaultIndicateUsing)
    },

    removedState() {
      return null
    },

    makeRenderer({ localValue, getResetValue }) {
      return () => {
        return (
          <NSelect
            placeholder={placeholder ?? title}
            value={localValue.value}
            onUpdate:value={(newValue: string | null) => {
              localValue.value = newValue || getResetValue()
            }}
            options={selectOptions}
            clearable
          />
        )
      }
    },
  }
}
