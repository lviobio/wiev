export default {
  Component: defineAsyncComponent(() => import('./PostEdit.vue')),
  ComponentForm: defineAsyncComponent(() => import('./PostEditForm.vue')),
}
