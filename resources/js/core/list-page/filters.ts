import type { TableFilter } from '@/components/AppDataTable/filters/base'
import { DateRangeFilter } from '@/components/AppDataTable/filters/DateRangeFilter'
import { SelectFilter } from '@/components/AppDataTable/filters/SelectFilter'
import { TextFilter } from '@/components/AppDataTable/filters/TextFilter'
import { startCase } from 'lodash'
import { z, type ZodObject, type ZodRawShape } from 'zod'
import type { FilterOverride } from './types'

// ── Zod schema introspection ────────────────────────────────────

/**
 * Unwrap Zod wrappers (nullable, optional, default) to get the base type.
 */
function unwrapZodType(schema: z.ZodType<any>): z.ZodType<any> {
  let current = schema
  while (
    current instanceof z.ZodOptional ||
    current instanceof z.ZodNullable ||
    current instanceof z.ZodDefault
  ) {
    current = (current as any).unwrap()
  }
  return current
}

/**
 * Detect if a Zod schema represents a date range shape: { from: number|null, to: number|null }
 */
function isDateRangeShape(schema: z.ZodType<any>): boolean {
  const base = unwrapZodType(schema)
  if (!(base instanceof z.ZodObject)) return false

  const shape = (base as z.ZodObject<any>).shape
  const keys = Object.keys(shape)

  if (keys.length !== 2 || !keys.includes('from') || !keys.includes('to')) return false

  const fromBase = unwrapZodType(shape.from)
  const toBase = unwrapZodType(shape.to)

  return fromBase instanceof z.ZodNumber && toBase instanceof z.ZodNumber
}

/**
 * Detect if a Zod schema is a string-like type (string, nullable string, etc.)
 */
function isStringLike(schema: z.ZodType<any>): boolean {
  const base = unwrapZodType(schema)
  return base instanceof z.ZodString
}

// ── Filter type inference ───────────────────────────────────────

type InferredFilterType = 'text' | 'daterange' | 'select'

function inferFilterType(
  schema: z.ZodType<any>,
  override?: FilterOverride,
): InferredFilterType | null {
  // Explicit type from override takes priority
  if (override?.type) return override.type

  // If options are provided, it's a select filter
  if (override?.options) return 'select'

  // Infer from Zod schema shape
  if (isDateRangeShape(schema)) return 'daterange'
  if (isStringLike(schema)) return 'text'

  return null
}

// ── Title generation ────────────────────────────────────────────

/**
 * Generate a human-readable title from a filter key.
 * `created_at` -> "Created At", `title` -> "Title"
 */
function humanizeKey(key: string): string {
  return startCase(key)
}

// ── defineFilters ───────────────────────────────────────────────

/**
 * Auto-generate TableFilter definitions from a Zod schema.
 *
 * Inference rules:
 * - `z.string().nullable()` -> TextFilter
 * - `z.object({ from: z.number().nullable(), to: z.number().nullable() })` -> DateRangeFilter
 * - override with `options` -> SelectFilter
 * - override with explicit `type` -> that type
 *
 * Title is auto-generated from key via `startCase()` unless overridden.
 *
 * @example
 * ```ts
 * const filters = defineFilters(postListFiltersSchema, {
 *   title: { placeholder: 'Search by title' },
 *   trashed: { options: trashedOptions },
 *   // created_at — auto-inferred as DateRangeFilter
 * })
 * ```
 */
export function defineFilters<T extends ZodRawShape>(
  schema: ZodObject<T>,
  overrides?: Partial<Record<keyof T & string, FilterOverride>>,
): TableFilter<string, any>[] {
  const shape = schema.shape
  const filters: TableFilter<string, any>[] = []

  for (const key of Object.keys(shape)) {
    const fieldSchema = shape[key] as unknown as z.ZodType<any>
    const override = overrides?.[key as keyof T & string]

    const filterType = inferFilterType(fieldSchema, override)
    if (!filterType) continue

    const title = override?.title ?? humanizeKey(key)

    switch (filterType) {
      case 'text': {
        const filter = TextFilter.make(key, title)
        if (override?.placeholder) filter.withPlaceholder(override.placeholder)
        filters.push(filter.toTableFilter())
        break
      }
      case 'daterange': {
        const filter = DateRangeFilter.make(key, title)
        filters.push(filter.toTableFilter())
        break
      }
      case 'select': {
        const filter = SelectFilter.make(key, title)
        if (override?.options) filter.withOptions(override.options)
        if (override?.placeholder) filter.withPlaceholder(override.placeholder)
        filters.push(filter.toTableFilter())
        break
      }
    }
  }

  return filters
}
