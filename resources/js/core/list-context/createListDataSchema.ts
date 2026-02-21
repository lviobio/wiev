import { z } from 'zod'

const sortFieldSchema = z.object({
  field: z.string(),
  order: z.enum(['asc', 'desc']),
})

/**
 * Builds a Zod schema for list context data by combining the
 * generic pagination / search / sort fields with a module-specific
 * filters schema.
 *
 * @example
 * ```ts
 * const postListDataSchema = createListDataSchema(postListFiltersSchema)
 * ```
 */
export function createListDataSchema<
  FS extends z.ZodType<FST>,
  FST extends Record<string, unknown>,
>(filters: FS) {
  return z.object({
    page: z.coerce.number().positive().optional(),
    cursor: z.coerce.string().optional(),
    per_page: z.coerce.number().optional(),
    search: z.coerce.string().optional(),
    sort: z.array(sortFieldSchema).optional(),
    filters: filters,
  })
}
