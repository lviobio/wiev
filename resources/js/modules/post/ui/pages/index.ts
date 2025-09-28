import { withPropsSchemaLazy } from '@/core/navigator/windows/withPropsSchema'
import { postEditPageSchema, postShowPageSchema } from './schemas'

export const List = () => import('./PostListPage.vue')

export const Create = () => import('./PostCreatePage.vue')

export const Show = withPropsSchemaLazy(() => import('./PostShowPage.vue'), postShowPageSchema)

export const Edit = withPropsSchemaLazy(() => import('./PostEditPage.vue'), postEditPageSchema)

export const Pages = {
  List,
  Create,
  Show,
  Edit,
} as const

export default Pages
