import { z } from 'zod'
import { toValidNumber } from 'zod-valid'

const postShowPageSchema = z.object({
  id: toValidNumber({ allow: 'none', preserve: false }),
})
export type PostShowPageSchema = z.infer<typeof postShowPageSchema>

const postEditPageSchema = z.object({
  id: toValidNumber({ allow: 'none', preserve: false }),
})
export type PostEditPageSchema = z.infer<typeof postEditPageSchema>

export { postEditPageSchema, postShowPageSchema }
