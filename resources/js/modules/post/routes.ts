import UI from '@/modules/post/ui'
import { RouteRecordRaw } from 'vue-router'

export const postRoutes: RouteRecordRaw[] = [
  {
    path: 'posts',
    component: () => import('@/modules/post/PostLayout.vue'),
    children: [
      {
        path: '',
        name: 'posts.index',
        component: UI.List.Page,
      },
      {
        path: 'create',
        name: 'posts.create',
        component: UI.Create.Page,
      },
      {
        path: ':id',
        name: 'posts.show',
        component: UI.Show.Page,
      },
    ],
  },
]
