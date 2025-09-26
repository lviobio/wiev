import { ModuleRouteGenerator } from '@/modules/_shared/interface'
import { routeParamsFallthrough } from '@/modules/_shared/routeParamsFallthrough'
import { PostIdentifier } from '@/modules/post/types'
import UI from '@/modules/post/ui'
import { RouteRecordRaw } from 'vue-router'

export const postRouteNames = {
  index: 'posts.index',
  create: 'posts.create',
  show: 'posts.show',
  edit: 'posts.edit',
}

export const postRouteGenerator: ModuleRouteGenerator = {
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
        component: routeParamsFallthrough(UI.List.Page),
      },
      {
        path: 'create',
        name: postRouteNames.create,
        component: routeParamsFallthrough(UI.Create.Page),
      },
      {
        path: ':id',
        name: postRouteNames.show,
        component: routeParamsFallthrough(UI.Show.Page),
      },
      {
        path: ':id/edit',
        name: postRouteNames.edit,
        component: routeParamsFallthrough(UI.Edit.Page),
      },
    ],
  },
]
