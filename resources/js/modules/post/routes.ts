import { PostIdentifier } from '@/modules/post/types'
import UI from '@/modules/post/ui'
import { RouteRecordRaw } from 'vue-router'

export const postRouteNames = {
  index: 'posts.index',
  create: 'posts.create',
  show: 'posts.show',
  edit: 'posts.edit',
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
}

export const postRoutes: RouteRecordRaw[] = [
  {
    path: 'posts',
    component: () => import('@/modules/post/PostLayout.vue'),
    children: [
      {
        path: '',
        name: postRouteNames.index,
        component: UI.List.Page,
      },
      {
        path: 'create',
        name: postRouteNames.create,
        component: UI.Create.Page,
      },
      {
        path: ':id',
        name: postRouteNames.show,
        component: UI.Show.Page,
      },
      {
        path: ':id/edit',
        name: postRouteNames.edit,
        component: UI.Edit.Page,
      },
    ],
  },
]
