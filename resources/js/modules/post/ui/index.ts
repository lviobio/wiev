export const List = {
  Page: () => import('./pages/PostListPage.vue'),
  Component: defineAsyncComponent(() => import('./components/PostList.vue')),
}

export const Create = {
  Page: () => import('./pages/PostCreatePage.vue'),
  Component: defineAsyncComponent(() => import('./components/PostCreate/PostCreate.vue')),
  ComponentForm: defineAsyncComponent(() => import('./components/PostCreate/PostCreateForm.vue')),
}

export const Show = {
  Page: () => import('./pages/PostShowPage.vue'),
  Component: defineAsyncComponent(() => import('./components/PostShow.vue')),
}

export default {
  List,
  Create,
  Show,
}
