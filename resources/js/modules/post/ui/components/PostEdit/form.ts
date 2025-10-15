import { transformSchemaForForm } from '@/core/utils/form-schemas'
import { postFormSchema } from '@/modules/post/repositories/PostRepository'
import { z } from 'zod'

export const postEditFormSchema = transformSchemaForForm(postFormSchema)
export type PostEditFormData = z.infer<typeof postEditFormSchema>
