export default {
  Component: defineAsyncComponent(() => import('./PostCreate.vue')),
  ComponentForm: defineAsyncComponent(() => import('./PostCreateForm.vue')),
}
