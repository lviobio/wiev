import { type Ref, VNodeChild } from 'vue'

// ── Indicator ───────────────────────────────────────────────────

export interface Indicator {
  label: string
  removeField: string | null
}

export function createIndicator(label: string, removeField: string | null = null): Indicator {
  return { label, removeField }
}

// ── Filter control (passed to renderer) ─────────────────────────

export interface MakeTableFilterControl<S> {
  localValue: Ref<S>
  autofocus: boolean
  getResetValue(): S
  onRender(): void
  confirm(): void
  remove(): void
}

// ── TableFilter interface ───────────────────────────────────────

export interface TableFilter<K extends string = string, S = unknown> {
  key: K
  title: string
  getIndicator: (filterState: S) => Indicator[]
  defaultState?(): S
  removedState?(): S
  makeRenderer(options: MakeTableFilterControl<S>): () => VNodeChild
}

// ── Indicator helpers ───────────────────────────────────────────

type IndicateUsingCallback<S> = (
  state: S,
  indicators: Array<string | Indicator>,
) => undefined | Array<string | Indicator>

/**
 * Resolve an `indicateUsing` callback into a normalized `Indicator[]`.
 * Shared logic extracted from the old `BaseFilter` class.
 */
export function resolveIndicators<S>(
  state: S,
  indicateUsing?: IndicateUsingCallback<S>,
): Indicator[] {
  if (!indicateUsing) return []

  const directItems: Array<string | Indicator> = []
  const items = indicateUsing(state, directItems) ?? directItems

  return items.map((item) => (typeof item === 'string' ? createIndicator(item) : item))
}
