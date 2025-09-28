export { default as Create } from './PostCreate'
export { default as Edit } from './PostEdit'

export const List = {
  Component: defineAsyncComponent(() => import('./PostList.vue')),
}

export const Show = {
  Component: defineAsyncComponent(() => import('./PostShow.vue')),
}

export default {
  List,
  Show,
}
