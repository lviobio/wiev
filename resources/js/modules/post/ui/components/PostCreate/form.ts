import { transformSchemaForForm } from '@/core/utils/form-schemas'
import { postFormSchema } from '@/modules/post/repositories/PostRepository'
import { z } from 'zod'

export const postCreateFormSchema = transformSchemaForForm(postFormSchema)
export type PostCreateFormData = z.infer<typeof postCreateFormSchema>
