import { makeDataTableFiltering } from '@/components/AppDataTable/filters'
import type { TableFilter } from '@/components/AppDataTable/filters/base'
import { defineFilters } from '@/core/crud/list-page/filters'
import { createFiltersSyncChannel } from '@/core/list-context/channels'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { reactive, toRef, watch, type Ref } from 'vue'
import type { z } from 'zod'
import { PaginationResetPageKey, type FeatureContext, type FiltersFeature } from './types'

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
    priority: 4000,
    install(ctx: FeatureContext) {
      const filters = reactive(createEmptyObjectFromSchema(filtersSchema) as F)

      watch(
        filters,
        () => {
          if (ctx.isHydrating()) return

          ctx.resolve<() => void>(PaginationResetPageKey)?.()
          ctx.loadDebounced()
        },
        { deep: true },
      )

      const resolvedItems = options.items ?? defineFilters(filtersSchema)
      // Bind the factory to the generic `Record<string, unknown>` state shape:
      // the per-key mapped `items` type then collapses to plain `TableFilter[]`,
      // which is what `defineFilters`/`options.items` produce.
      const filtering = resolvedItems.length
        ? makeDataTableFiltering<Record<string, unknown>>(
            toRef(() => filters) as Ref<Record<string, unknown>>,
            resolvedItems as TableFilter[],
          )
        : undefined

      return {
        state: { filters } as { filters: import('vue').Reactive<F> },
        contributions: filtering ? { table: { filtering } } : undefined,
        contextSync: [createFiltersSyncChannel(filters)],
      }
    },
  }
}
