import {
  BaseFilter,
  Indicator,
  MakeTableFilterControl,
} from '@/components/AppDataTable/filters/base'
import { useDateFormatters } from '@/core/helpers'
import { DatePickerInst, NDatePicker, NFlex } from 'naive-ui'

/**
 * Date range filter
 */
export class DateRangeFilter<K extends string = string> extends BaseFilter<
  K,
  { from: number | null; to: number | null }
> {
  private placeholderFrom?: string
  private placeholderTo?: string

  static make<K extends string>(key: K, title: string): DateRangeFilter<K> {
    const filter = new DateRangeFilter(key, title)

    const { formatDate } = useDateFormatters()

    filter.indicateUsing((state) => {
      const indicators: Indicator[] = []
      if (state.from !== null) {
        const indicator = Indicator.make(`${filter.title} from: ${formatDate(state.from)}`)
        indicator.removeField = 'from'
        indicators.push(indicator)
      }
      if (state.to !== null) {
        const indicator = Indicator.make(`${filter.title} to: ${formatDate(state.to)}`)
        indicator.removeField = 'to'
        indicators.push(indicator)
      }
      return indicators
    })
    return filter
  }

  /**
   * Set custom placeholders for date pickers
   */
  withPlaceholders(from: string, to: string): this {
    this.placeholderFrom = from
    this.placeholderTo = to
    return this
  }

  removedState(): { from: number | null; to: number | null } {
    return { from: null, to: null }
  }

  protected renderFilter({
    localValue,
    confirm,
    autofocus,
  }: MakeTableFilterControl<{ from: number | null; to: number | null }>) {
    const placeholderFrom = this.placeholderFrom ?? 'From'
    const placeholderTo = this.placeholderTo ?? 'To'

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
  }
}
