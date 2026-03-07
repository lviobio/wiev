import { TableFilter } from '@/components/AppDataTable/filters/base'
import { DateRangeFilter } from '@/components/AppDataTable/filters/DateRangeFilter'
import { SelectFilter } from '@/components/AppDataTable/filters/SelectFilter'
import { TextFilter } from '@/components/AppDataTable/filters/TextFilter'
import { createEmptyObjectFromSchema, getSchemaFromObject } from '@/core/utils/form-schemas'
import { cloneDeep } from 'lodash'
import { type Ref, VNodeChild } from 'vue'

export { DateRangeFilter, SelectFilter, TextFilter }

/**
 * remove - reset filter to empty state
 * reset - reset filter to default state
 */

export type FiltersState = Record<string, unknown>

export interface TableFiltering<S extends FiltersState = FiltersState> {
  state: Ref<S>
  items: Array<
    {
      [K in keyof S & string]: TableFilter<K, S[K]>
    }[keyof S & string]
  >
}

export interface TableFilterController<K extends string = string, S = unknown> {
  filter: TableFilter<K, S>
  localValue: Ref<S>
  render: () => VNodeChild
  confirm: () => void
  remove: () => void
}

// export type FiltersState = Record<string, unknown>

export function getFilterRef<FS extends FiltersState, K extends keyof FS & string>(
  filter: { key: K },
  state: Ref<FS>,
) {
  return toRef(state.value, filter.key as keyof typeof state.value)
}

export function getRemovedFilterValue<
  FS extends FiltersState,
  K extends keyof FS & string,
  S extends FS[K],
>(filter: { key: K; removedState?(): S }, state: FS): S {
  if (filter.removedState) {
    return filter.removedState()
  }

  // Автоматический поиск значения на основе схемы
  const schema = getSchemaFromObject(state)
  if (!schema) {
    throw new Error(
      `Cannot get removed state for filter "${filter.key}": no schema found in state. ` +
        `Please provide a custom removedState() method for this filter.`,
    )
  }
  const emptyState = createEmptyObjectFromSchema(schema, false)
  return emptyState[filter.key] as S
}

export function makeFilterController<
  FS extends FiltersState,
  K extends keyof FS & string,
  S extends FS[K],
>({
  filter,
  state,
  after,
  autofocus,
}: {
  filter: TableFilter<K, S>
  state: Ref<FS>
  after?: () => void
  autofocus: boolean
}): TableFilterController<K, S> {
  const filterRef = getFilterRef(filter, state)
  const localValue = ref(cloneDeep(filterRef.value))

  watch(
    filterRef,
    () => {
      localValue.value = cloneDeep(filterRef.value)
    },
    {
      deep: true,
    },
  )

  const confirm = () => {
    filterRef.value = localValue.value
    after?.()
  }

  const remove = () => {
    state.value[filter.key] = getRemovedFilterValue(filter, state.value)
    after?.()
  }

  const render = filter.makeRenderer({
    localValue,
    onRender() {},
    getResetValue() {
      return getRemovedFilterValue(filter, state.value)
    },
    confirm,
    remove,
    autofocus: autofocus,
  })

  return {
    filter,
    localValue,
    render,
    confirm,
    remove,
  }
}

export function makeDataTableFiltering<FS extends FiltersState>(
  state: Ref<FS>,
  items: Array<
    {
      [K in keyof FS & string]: TableFilter<K, FS[K]>
    }[keyof FS & string]
  >,
): TableFiltering<FS> {
  return {
    state,
    items,
  }
}
