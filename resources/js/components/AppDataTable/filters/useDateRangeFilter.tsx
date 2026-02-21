import {
  createIndicator,
  resolveIndicators,
  type TableFilter,
} from '@/components/AppDataTable/filters/base'
import { useDateFormatters } from '@/core/helpers'
import { DatePickerInst, NDatePicker, NFlex } from 'naive-ui'

type DateRangeState = { from: number | null; to: number | null }

export interface UseDateRangeFilterOptions {
  placeholderFrom?: string
  placeholderTo?: string
  indicateUsing?: (
    state: DateRangeState,
    indicators: Array<string | import('./base').Indicator>,
  ) => undefined | Array<string | import('./base').Indicator>
}

export function useDateRangeFilter<K extends string>(
  key: K,
  title: string,
  options: UseDateRangeFilterOptions = {},
): TableFilter<K, DateRangeState> {
  const { placeholderFrom = 'From', placeholderTo = 'To', indicateUsing } = options
  const { formatDate } = useDateFormatters()

  const defaultIndicateUsing =
    indicateUsing ??
    ((state) => {
      const indicators: import('./base').Indicator[] = []
      if (state.from !== null) {
        indicators.push(createIndicator(`${title} from: ${formatDate(state.from)}`, 'from'))
      }
      if (state.to !== null) {
        indicators.push(createIndicator(`${title} to: ${formatDate(state.to)}`, 'to'))
      }
      return indicators
    })

  return {
    key,
    title,

    getIndicator(state) {
      return resolveIndicators(state, defaultIndicateUsing)
    },

    removedState() {
      return { from: null, to: null }
    },

    makeRenderer({ localValue, confirm, autofocus }) {
      return () => {
        const inputFromRef = ref<DatePickerInst>()
        const inputToRef = ref<DatePickerInst>()
        const showInputTo = ref(false)

        watch(inputFromRef, () => {
          if (!autofocus) return
          inputFromRef.value?.focus()
        })

        function next() {
          inputToRef.value?.focus()
          showInputTo.value = true
        }

        const inputFromProps = {
          onKeyup: (e: KeyboardEvent) => {
            if (e.key === 'Enter') {
              next()
            }
          },
        }

        const inputToProps = {
          onKeyup: (e: KeyboardEvent) => {
            if (e.key === 'Enter') {
              confirm()
            }
          },
        }

        return (
          <NFlex>
            <NDatePicker
              ref={inputFromRef}
              {...inputFromProps}
              placeholder={placeholderFrom}
              value={localValue.value.from}
              onUpdate:value={(newValue) => (localValue.value.from = newValue || null)}
              clearable
            />
            <NDatePicker
              ref={inputToRef}
              {...inputToProps}
              placeholder={placeholderTo}
              value={localValue.value.to}
              onUpdate:value={(newValue) => (localValue.value.to = newValue || null)}
              show={showInputTo.value}
              onUpdate:show={(show) => (showInputTo.value = show)}
              clearable
            />
          </NFlex>
        )
      }
    },
  }
}
