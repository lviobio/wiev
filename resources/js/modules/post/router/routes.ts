import { routeParamsFallthrough } from '@/modules/_shared/routeParamsFallthrough'
import { postRouteNames } from '@/modules/post/router/names'
import Pages from '@/modules/post/ui/pages'
import { RouteRecordRaw } from 'vue-router'

export const postRoutes = [
  {
    path: 'posts',
    component: () => import('@/modules/post/PostLayout.vue'),
    children: [
      {
        path: '',
        name: postRouteNames.index,
        component: routeParamsFallthrough(Pages.List),
      },
      {
        path: 'create',
        name: postRouteNames.create,
        component: routeParamsFallthrough(Pages.Create),
      },
      {
        path: ':id',
        name: postRouteNames.show,
        component: routeParamsFallthrough(Pages.Show),
      },
      {
        path: ':id/edit',
        name: postRouteNames.edit,
        component: routeParamsFallthrough(Pages.Edit),
      },
    ],
  },
] as const satisfies readonly RouteRecordRaw[]
