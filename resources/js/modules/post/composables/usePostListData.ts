import { createListDataSchema } from '@/core/list-context/createListDataSchema'
import { ListContextConstraint } from '@/core/list-context/useListContextSync'
import { createContext } from '@/core/utils/context'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { PostListFilters, postListFiltersSchema } from '@/modules/post/repositories/PostRepository'

export type ListContextData<F> = ListContextConstraint<F>

export const postListDataSchema = createListDataSchema(postListFiltersSchema)

export const makeContextData = (): Ref<ListContextData<PostListFilters>> =>
  ref(createEmptyObjectFromSchema(postListDataSchema))

const contextKey: InjectionKey<ReturnType<typeof makeContextData>> = Symbol()

export function usePostListContext() {
  return createContext(contextKey, makeContextData)
}
