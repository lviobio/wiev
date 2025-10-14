import { transformSchemaForForm } from '@/core/utils/form-schemas'
import { postFormSchema } from '@/modules/post/repositories/PostRepository'
import { z } from 'zod'

export default {
  Component: defineAsyncComponent(() => import('./PostCreate.vue')),
  ComponentForm: defineAsyncComponent(() => import('./PostCreateForm.vue')),
}

export const postCreateFormSchema = transformSchemaForForm(postFormSchema)
export type PostCreateFormData = z.infer<typeof postCreateFormSchema>
