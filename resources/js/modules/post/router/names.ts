import { ModuleRouteGenerator } from '@/modules/_shared/interface'
import { PostIdentifier } from '@/modules/post/types'

export const postRouteNames = {
  index: 'posts.index' as const,
  create: 'posts.create' as const,
  show: 'posts.show' as const,
  edit: 'posts.edit' as const,
}

export const postRouteGenerator = {
  index: () => ({
    name: postRouteNames.index,
  }),
  show: (id: PostIdentifier) => ({
    name: postRouteNames.show,
    params: { id },
  }),
  edit: (id: PostIdentifier) => ({
    name: postRouteNames.edit,
    params: { id },
  }),
  create: () => ({
    name: postRouteNames.create,
  }),
} satisfies ModuleRouteGenerator
