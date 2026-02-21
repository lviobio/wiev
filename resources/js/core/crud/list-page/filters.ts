import type { TableFilter } from '@/components/AppDataTable/filters/base'
import { useDateRangeFilter } from '@/components/AppDataTable/filters/useDateRangeFilter'
import { useSelectFilter } from '@/components/AppDataTable/filters/useSelectFilter'
import { useTextFilter } from '@/components/AppDataTable/filters/useTextFilter'
import { pick, startCase } from 'lodash'
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

// ── Schema metadata ─────────────────────────────────────────────

/**
 * Read filter metadata from a Zod schema's `.meta()`.
 * Returns an empty object if no metadata is set.
 */
function readSchemaMeta(schema: z.ZodType<any>): FilterOverride {
  const meta = (schema as any).meta?.() as FilterOverride | undefined
  return meta ?? {}
}

/**
 * Merge schema meta with explicit overrides. Overrides take priority.
 */
function mergeOverrides(schemaMeta: FilterOverride, override?: FilterOverride): FilterOverride {
  return { ...schemaMeta, ...override }
}

// ── Filter type inference ───────────────────────────────────────

type InferredFilterType = 'text' | 'daterange' | 'select'

function inferFilterType(
  schema: z.ZodType<any>,
  merged: FilterOverride,
): InferredFilterType | null {
  // Explicit type takes priority
  if (merged.type) return merged.type

  // If options are provided, it's a select filter
  if (merged.options) return 'select'

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
 * Filter metadata can be embedded directly in the Zod schema via `.meta()`:
 * ```ts
 * z.string().nullable().meta({ placeholder: 'Search by title' })
 * ```
 *
 * Explicit `overrides` take priority over schema `.meta()`.
 *
 * Inference rules:
 * - `z.string().nullable()` -> useTextFilter
 * - `z.object({ from: z.number().nullable(), to: z.number().nullable() })` -> useDateRangeFilter
 * - `options` in meta or override -> useSelectFilter
 * - explicit `type` in meta or override -> that type
 *
 * Title is auto-generated from key via `startCase()` unless overridden.
 *
 * @example
 * ```ts
 * // Metadata in schema — no overrides needed:
 * defineFilters(postListFiltersSchema)
 *
 * // Or with overrides (take priority over .meta()):
 * defineFilters(postListFiltersSchema, {
 *   title: { placeholder: 'Custom placeholder' },
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
    const schemaMeta = readSchemaMeta(fieldSchema)
    const merged = mergeOverrides(schemaMeta, overrides?.[key as keyof T & string])

    const filterType = inferFilterType(fieldSchema, merged)
    if (!filterType) continue

    const title = merged.title ?? humanizeKey(key)

    switch (filterType) {
      case 'text': {
        filters.push(useTextFilter(key, title, pick(merged, ['placeholder'])))
        break
      }
      case 'daterange': {
        filters.push(useDateRangeFilter(key, title))
        break
      }
      case 'select': {
        filters.push(useSelectFilter(key, title, pick(merged, ['options', 'placeholder'])))
        break
      }
    }
  }

  return filters
}
