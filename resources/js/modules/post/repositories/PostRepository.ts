import {
  DefaultCreateQueryContract,
  DefaultCreateQueryResultContract,
  DefaultFindQueryResultContract,
  DefaultListQueryContract,
  DefaultUpdateQueryContract,
  DefaultUpdateQueryResultContract,
  sendAxiosGetRequest,
  sendAxiosPostRequestJson,
  sendAxiosPutRequestJson,
} from '@/core/api/simple-repository-helpers-v1/main'
import { zFilterTrashed } from '@/core/filters/trashed'
import { MaybePaginatedData } from '@/core/pagination/base'
import { AxiosInstance } from 'axios'
import { z } from 'zod'
import { Post, PostIdentifier } from '../types'

export const postListFiltersSchema = z.object({
  title: z.string().nullable().meta({ placeholder: 'Search by title' }),
  created_at: z.object({
    from: z.number().nullable(),
    to: z.number().nullable(),
  }),
  trashed: zFilterTrashed,
})

/** List */
export type PostListFilters = z.infer<typeof postListFiltersSchema>

interface PostListQuery extends DefaultListQueryContract<{
  filter?: PostListFilters
  search?: string
}> {}

type PostListQueryResult = MaybePaginatedData<Post>

export const postFormSchema = z.object({
  title: z.string(),
  content: z.string().nullable(),
  // The API's actual wire type is `string | null` (a temporary-upload identifier),
  // not `File` - kept as `File` here so `transformSchemaForForm`'s file-field
  // detection still treats this as an upload field. `prepareFormData` (see
  // core/utils/form-schemas.ts) is what actually resolves it to the identifier.
  cover: z.custom<File>().nullish(),
})

type PostFormSchema = z.infer<typeof postFormSchema>

/**
 * The shape actually sent to the API once `prepareFormData()` has run: `cover` is a
 * temporary-upload identifier by then (see `AppIllustrationInput.vue`'s
 * `customRequest`), not the raw `File` `postFormSchema` declares above - that
 * declaration exists only so `transformSchemaForForm()` treats the field as an upload
 * field in the first place.
 */
type PostWireData = Omit<PostFormSchema, 'cover'> & { cover?: string | null }

/** Create */
type PostCreateData = PostWireData

type PostCreateQuery = DefaultCreateQueryContract<PostCreateData>
type PostCreateQueryResult = DefaultCreateQueryResultContract<Post>

/** Update */
type PostUpdateData = PostWireData

type PostUpdateQuery = DefaultUpdateQueryContract<PostUpdateData>
type PostUpdateQueryResult = DefaultUpdateQueryResultContract<Post>

/** Other */
type PostFindResult = DefaultFindQueryResultContract<Post>
type PostDeleteResult = void

export interface PostRepository {
  list(options: PostListQuery): Promise<PostListQueryResult>
  find(id: PostIdentifier): Promise<PostFindResult>
  create(options: PostCreateQuery): Promise<PostCreateQueryResult>
  update(id: PostIdentifier, options: PostUpdateQuery): Promise<PostUpdateQueryResult>
  delete(id: PostIdentifier): Promise<PostDeleteResult>
}

class PostApiRepository implements PostRepository {
  private readonly axios: AxiosInstance

  constructor() {
    this.axios = useAxios()
  }

  async list(options: PostListQuery) {
    const { data } = await sendAxiosGetRequest<PostListQueryResult>(this.axios, 'posts', options)

    return data
  }

  async find(id: PostIdentifier) {
    const { data } = await this.axios.get<PostFindResult>(`posts/${id}`)

    return data
  }

  async create(options: PostCreateQuery) {
    const { data } = await sendAxiosPostRequestJson<PostCreateQueryResult>(
      this.axios,
      'posts',
      options,
    )

    return data
  }

  async update(id: PostIdentifier, options: PostUpdateQuery) {
    const { data } = await sendAxiosPutRequestJson<PostUpdateQueryResult>(
      this.axios,
      `posts/${id}`,
      options,
    )

    return data
  }

  async delete(id: PostIdentifier) {
    await this.axios.delete(`posts/${id}`)
  }
}

export function usePostRepository(): PostRepository {
  return new PostApiRepository()
}

// resources/js/modules/post/repositories/PostRepository.ts

// Схема формы (с UploadFileInfo)

// export const postFormSchema = z.object({
//   title: z.string(),
//   content: z.string().nullable(),
//   cover: zFormFile,
//   // cover: z.codec(z.string().nullish(), z.custom<UploadFileInfo>().optional(), {
//   //   decode: (value) => {
//   //     console.log('decode', value, urlToUploadFileInfo.parse(value))
//   //
//   //     return urlToUploadFileInfo.parse(value)
//   //   },
//   //   encode: (value) => value?.file,
//   // }),
// })

// /**
//  * Идея: Cover превращать в объект с двумя полями: {url: string, file?: File}. Когда с бэка в cover приходит строка - преобразуем её в этот объект с помощью zod'a
//  */

// Схема для API (с File)
// export const postDataSchema2 = formDataWithFile({
//   title: z.string(),
//   content: z.string(),
//   cover: z.custom<File>().optional(),
// })
//
// export type PostFormData2 = z.infer<typeof postFormSchema2>
// export type PostCreateData2 = z.infer<typeof postDataSchema2>
