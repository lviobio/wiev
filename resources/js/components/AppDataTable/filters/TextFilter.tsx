import {
  BaseFilter,
  Indicator,
  MakeTableFilterControl,
} from '@/components/AppDataTable/filters/base'
import { InputInst, NInput } from 'naive-ui'

/**
 * Text input filter
 */
export class TextFilter<K extends string = string> extends BaseFilter<K, string | null> {
  private placeholder?: string

  static make<K extends string>(key: K, title: string): TextFilter<K> {
    const filter = new TextFilter(key, title)
    // Default indicateUsing implementation
    filter.indicateUsing((state) => {
      if (!state) return []
      return [Indicator.make(`${filter.title}: ${state}`)]
    })
    return filter
  }

  /**
   * Set input placeholder
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
    autofocus,
  }: MakeTableFilterControl<string | null>) {
    const placeholder = this.placeholder
    const title = this.title

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
  }
}
