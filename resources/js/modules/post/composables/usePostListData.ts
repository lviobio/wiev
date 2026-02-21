import { createContext } from '@/core/utils/context'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { PostListFilters, postListFiltersSchema } from '@/modules/post/repositories/PostRepository'

export interface ListContextData<F> {
  page?: number
  cursor?: string
  per_page?: number
  search?: string
  filters: F
}

export const makeContextData = (): Ref<ListContextData<PostListFilters>> =>
  ref({
    page: undefined as number | undefined,
    cursor: undefined as string | undefined,
    per_page: undefined as number | undefined,
    search: undefined as string | undefined,
    filters: createEmptyObjectFromSchema(postListFiltersSchema),
  })

const contextKey: InjectionKey<ReturnType<typeof makeContextData>> = Symbol()

export function usePostListContext() {
  return createContext(contextKey, makeContextData)
}
