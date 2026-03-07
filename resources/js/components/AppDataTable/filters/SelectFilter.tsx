import {
  BaseFilter,
  Indicator,
  MakeTableFilterControl,
} from '@/components/AppDataTable/filters/base'
import { NSelect, type SelectOption } from 'naive-ui'

/**
 * Select (dropdown) filter
 */
export class SelectFilter<K extends string = string> extends BaseFilter<K, string | null> {
  private placeholder?: string
  private selectOptions: SelectOption[] = []

  static make<K extends string>(key: K, title: string): SelectFilter<K> {
    const filter = new SelectFilter(key, title)
    // Default indicateUsing implementation
    filter.indicateUsing((state) => {
      if (!state) return []
      const option = filter.selectOptions.find((o) => o.value === state)
      const label = option?.label ?? state
      return [Indicator.make(`${filter.title}: ${label}`)]
    })
    return filter
  }

  /**
   * Set select options
   */
  withOptions(options: SelectOption[]): this {
    this.selectOptions = options
    return this
  }

  /**
   * Set select placeholder
   */
  withPlaceholder(placeholder: string): this {
    this.placeholder = placeholder
    return this
  }

  removedState(): string | null {
    return null
  }

  protected renderFilter({
    localValue,
    confirm,
    getResetValue,
  }: MakeTableFilterControl<string | null>) {
    const placeholder = this.placeholder
    const title = this.title
    const options = this.selectOptions

    return () => {
      return (
        <NSelect
          placeholder={placeholder ?? title}
          value={localValue.value}
          onUpdate:value={(newValue: string | null) => {
            localValue.value = newValue || getResetValue()
            confirm()
          }}
          options={options}
          clearable
        />
      )
    }
  }
}
