import { createContext } from '@/core/utils/context'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { postListFiltersSchema } from '@/modules/post/repositories/PostRepository'

const makeContextData = () => ({
  filters: ref(createEmptyObjectFromSchema(postListFiltersSchema)),
})

const contextKey: InjectionKey<ReturnType<typeof makeContextData>> = Symbol()

export function usePostListContext() {
  return createContext(contextKey, makeContextData)
}
