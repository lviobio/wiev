import { z } from 'zod'

export const FilterTrashedValues = ['with', 'only'] as const

export type FilterTrashed = (typeof FilterTrashedValues)[number] | null

export const trashedOptions = [
  { label: 'With Trashed', value: 'with' },
  { label: 'Only Trashed', value: 'only' },
]

export const zFilterTrashed = z.enum(FilterTrashedValues).nullable()
