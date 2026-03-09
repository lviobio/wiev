import { z, ZodObject, ZodRawShape } from 'zod'

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
export function createListDataSchema<F extends ZodRawShape>(filtersSchema: ZodObject<F>) {
  return z.object({
    page: z.coerce.number().positive().optional(),
    cursor: z.coerce.string().optional(),
    per_page: z.coerce.number().optional(),
    search: z.coerce.string().optional(),
    sort: z.array(sortFieldSchema).optional(),
    filters: filtersSchema,
  })
}
