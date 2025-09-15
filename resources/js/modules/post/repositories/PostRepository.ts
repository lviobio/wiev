import {
  DefaultCreateQueryContract,
  DefaultCreateQueryResultContract,
  DefaultListQueryContract,
  sendAxiosGetRequest,
  sendAxiosPostRequest,
} from '@/core/api/simple-repository-helpers-v1/main'
import { FilterTrashed } from '@/core/filters/trashed'
import { PaginatedData } from '@/core/pagination/base'
import { AxiosInstance } from 'axios'
import { Post, PostIdentifier } from '../types'

/** List */
export interface PostListFilters {
  search?: string
  trashed?: FilterTrashed
}

interface PostListQuery
  extends DefaultListQueryContract<{
    filters: PostListFilters
  }> {}

type PostListQueryResult = PaginatedData<Post>

/** Create */
export interface PostCreateData {
  title: string
  content: string
}

type PostCreateQuery = DefaultCreateQueryContract<PostCreateData>

type PostCreateQueryResult = DefaultCreateQueryResultContract<Post>

type PostFindResult = Post
type PostDeleteResult = void

export interface PostRepository {
  list(options: PostListQuery): Promise<PostListQueryResult>
  find(id: PostIdentifier): Promise<PostFindResult>
  create(options: PostCreateQuery): Promise<PostCreateQueryResult>
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
    const { data } = await this.axios.get<Post>(`posts/${id}`)

    return data
  }

  async create(options: PostCreateQuery) {
    const { data } = await sendAxiosPostRequest<PostCreateQueryResult>(this.axios, 'posts', options)

    return data
  }

  async delete(id: PostIdentifier) {
    await this.axios.delete(`posts/${id}`)
  }
}

export function usePostRepository(): PostRepository {
  return new PostApiRepository()
}
