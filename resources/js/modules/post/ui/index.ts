import { withPropsSchemaLazy } from '@/core/windows/withPropsSchema'
import { propsSchema as postEditPageSchema } from './pages/PostEditPage.vue'
import { propsSchema as postShowPageSchema } from './pages/PostShowPage.vue'

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
  Page: withPropsSchemaLazy(
    () => import('./pages/PostShowPage.vue'),
    postShowPageSchema,
  ),
  Component: defineAsyncComponent(() => import('./components/PostShow.vue')),
}

export const Edit = {
  Page: withPropsSchemaLazy(
    () => import('./pages/PostEditPage.vue'),
    postEditPageSchema,
  ),
  Component: defineAsyncComponent(() => import('./components/PostEdit/PostEdit.vue')),
  ComponentForm: defineAsyncComponent(() => import('./components/PostEdit/PostEditForm.vue')),
}

export default {
  List,
  Create,
  Show,
  Edit,
}
