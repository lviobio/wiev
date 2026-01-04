import { z } from 'zod'

export const FilterTrashedValues = ['with', 'only'] as const

export type FilterTrashed = 'with' | 'only' | null

export const trashedOptions = [
  { label: 'With Trashed', value: 'with' },
  { label: 'Only Trashed', value: 'only' },
]

export const zFilterTrashed = z.enum(['with', 'only']).nullable()
