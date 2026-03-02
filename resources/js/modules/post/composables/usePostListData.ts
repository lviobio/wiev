import { ListContextConstraint } from '@/core/list-context/useListContextSync'
import { createContext } from '@/core/utils/context'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { PostListFilters, postListFiltersSchema } from '@/modules/post/repositories/PostRepository'
import { z } from 'zod'

export type ListContextData<F> = ListContextConstraint<F>

export const postListDataSchema = z.object({
  page: z.coerce.number().positive().optional(),
  cursor: z.coerce.string().optional(),
  per_page: z.coerce.number().optional(),
  search: z.coerce.string().optional(),
  sort: z
    .array(
      z.object({
        field: z.string(),
        order: z.enum(['asc', 'desc']),
      }),
    )
    .optional(),
  filters: postListFiltersSchema,
})

export const makeContextData = (): Ref<ListContextData<PostListFilters>> =>
  ref(createEmptyObjectFromSchema(postListDataSchema))

const contextKey: InjectionKey<ReturnType<typeof makeContextData>> = Symbol()

export function usePostListContext() {
  return createContext(contextKey, makeContextData)
}
