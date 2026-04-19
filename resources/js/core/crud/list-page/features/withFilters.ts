import { makeDataTableFiltering } from '@/components/AppDataTable/filters'
import type { TableFilter } from '@/components/AppDataTable/filters/base'
import { defineFilters } from '@/core/crud/list-page/filters'
import { createFiltersSyncChannel } from '@/core/list-context/channels'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { reactive, toRef, watch } from 'vue'
import type { z } from 'zod'
import { type FeatureContext, type FiltersFeature, PaginationResetPageKey } from './types'

export interface WithFiltersOptions {
  /**
   * Manual list of `TableFilter` items. If omitted, filters are
   * auto-inferred from the Zod schema via `defineFilters()`.
   */
  items?: TableFilter[]
}

export function withFilters<FS extends z.ZodObject>(
  filtersSchema: FS,
  options: WithFiltersOptions = {},
): FiltersFeature<z.infer<FS>> {
  type F = z.infer<FS>

  return {
    brand: 'filters',
    priority: 4000,
    install(ctx: FeatureContext) {
      const filters = reactive(createEmptyObjectFromSchema(filtersSchema) as F)

      watch(
        filters,
        () => {
          ctx.resolve<() => void>(PaginationResetPageKey)?.()
          ctx.loadDebounced()
        },
        { deep: true },
      )

      const resolvedItems = options.items ?? defineFilters(filtersSchema)
      const filtering = resolvedItems.length
        ? makeDataTableFiltering(toRef(() => filters) as any, resolvedItems as any)
        : undefined

      return {
        state: { filters } as { filters: import('vue').Reactive<F> },
        contributions: filtering ? { table: { filtering } } : undefined,
        contextSync: [createFiltersSyncChannel(filters)],
      }
    },
  }
}
