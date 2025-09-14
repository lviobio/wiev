import { RouteRecordRaw } from 'vue-router'

export const postRoutes: RouteRecordRaw[] = [
  {
    path: 'posts',
    component: () => import('@/modules/post/PostsLayout.vue'),
    children: [
      {
        path: '',
        name: 'posts.index',
        component: () => import('@/modules/post/ui/pages/PostsIndex.vue'),
      },
    ],
  },
]
