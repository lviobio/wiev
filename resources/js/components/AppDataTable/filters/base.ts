import { type Ref, VNodeChild } from 'vue'

/**
 * Represents a single indicator for active filter
 */
export class Indicator {
  removeField: string | null = null

  constructor(public label: string) {}

  static make(label: string) {
    return new Indicator(label)
  }
}

type IndicateUsingCallback<S> = (
  state: S,
  indicators: Array<string | Indicator>,
) => undefined | Array<string | Indicator>

export interface MakeTableFilterControl<S> {
  localValue: Ref<S>
  autofocus: boolean
  getResetValue(): S
  onRender(): void
  confirm(): void
  remove(): void
}

export interface TableFilter<K extends string = string, S = unknown> {
  key: K
  title: string
  getIndicator: (filterState: S) => Indicator[]
  defaultState?(): S
  removedState?(): S
  makeRenderer(options: MakeTableFilterControl<S>): () => VNodeChild
}

/**
 * Base class for all filters. Provides fluent API for configuration.
 */
export abstract class BaseFilter<K extends string = string, S = unknown> {
  key: K
  title: string
  protected _indicateUsing?: IndicateUsingCallback<S>

  constructor(key: K, title: string) {
    this.key = key
    this.title = title
  }

  /**
   * Set custom indicator callback that returns array of indicators
   */
  indicateUsing(callback: IndicateUsingCallback<S>): this {
    this._indicateUsing = callback
    return this
  }

  /**
   * Get single indicator for active filter badge
   * Can return string or Indicator object
   */
  getIndicator(state: S): string | Indicator {
    // Default implementation - delegates to getIndicators
    const indicators = this.getIndicatorsArray(state)
    if (indicators.length === 0) return ''
    if (indicators.length === 1) return indicators[0]
    return indicators.map((i) => i.label).join(' | ')
  }

  /**
   * Get indicators array for active filter badges
   * Internal method that handles indicateUsing callback
   */
  private getIndicatorsArray(state: S): Indicator[] {
    const directItems: Array<string | Indicator> = []
    let items = this._indicateUsing?.(state, directItems)
    if (!items && !directItems.length) {
      items = directItems
    }

    if (!items) {
      // Fallback to getIndicator method
      const indicator = this.getIndicator(state)
      if (typeof indicator === 'string') {
        return indicator ? [Indicator.make(indicator)] : []
      }
      return [indicator]
    }

    const result: Indicator[] = []

    for (const item of items) {
      let indicator: Indicator
      if (typeof item === 'string') {
        indicator = Indicator.make(item)
      } else {
        indicator = item
      }
      result.push(indicator)
    }

    return result
  }

  /**
   * Get indicators array - public method for external use
   */
  getIndicators(state: S): Indicator[] {
    return this.getIndicatorsArray(state)
  }

  /**
   * Get removed/default state (optional, can be auto-generated in subclasses)
   */
  abstract removedState(): S

  /**
   * Render filter UI
   */
  protected abstract renderFilter(options: MakeTableFilterControl<S>): () => VNodeChild

  /**
   * Convert to TableFilter interface
   */
  toTableFilter(): TableFilter<K, S> {
    return {
      key: this.key,
      title: this.title,
      getIndicator: (filterState) => this.getIndicators(filterState),
      removedState: this.removedState,
      makeRenderer: this.renderFilter,
    }
  }
}
