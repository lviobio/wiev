export const Create = {
  Page: () => import('./pages/PostCreatePage.vue'),
  Component: defineAsyncComponent(() => import('./components/PostCreate/PostCreate.vue')),
  ComponentForm: defineAsyncComponent(() => import('./components/PostCreate/PostCreateForm.vue')),
}

export const List = {
  Page: () => import('./pages/PostListPage.vue'),
  Component: defineAsyncComponent(() => import('./components/PostList.vue')),
}

export default {
  Create,
  List,
}
