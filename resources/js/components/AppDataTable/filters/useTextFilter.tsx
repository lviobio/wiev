import {
  createIndicator,
  resolveIndicators,
  type TableFilter,
} from '@/components/AppDataTable/filters/base'
import { InputInst, NInput } from 'naive-ui'

export interface UseTextFilterOptions {
  placeholder?: string
  indicateUsing?: (
    state: string | null,
    indicators: Array<string | import('./base').Indicator>,
  ) => undefined | Array<string | import('./base').Indicator>
}

export function useTextFilter<K extends string>(
  key: K,
  title: string,
  options: UseTextFilterOptions = {},
): TableFilter<K, string | null> {
  const { placeholder, indicateUsing } = options

  const defaultIndicateUsing =
    indicateUsing ??
    ((state) => {
      if (!state) return []
      return [createIndicator(`${title}: ${state}`)]
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

    makeRenderer({ localValue, confirm, getResetValue, autofocus }) {
      const onUpdateValue = (newValue: string) => {
        localValue.value = newValue || getResetValue()
      }

      return () => {
        const inputRef = shallowRef<InputInst>()

        watch(inputRef, () => {
          if (!autofocus) return
          inputRef.value?.focus()
        })

        return (
          <NInput
            ref={inputRef}
            onKeyup={(e) => {
              if (e.key === 'Enter') {
                confirm()
              }
            }}
            placeholder={placeholder ?? title}
            value={localValue.value}
            onUpdate:value={onUpdateValue}
            clearable
          />
        )
      }
    },
  }
}
