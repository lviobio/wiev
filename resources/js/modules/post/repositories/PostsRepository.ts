import {
  DefaultIndexQueryContract,
  DefaultStoreQueryContract,
  sendAxiosGetRequest,
  sendAxiosPostRequest,
} from '@/core/api/simple-repository-helpers-v1/main'
import { FilterTrashed } from '@/core/filters/trashed'
import { PaginatedData } from '@/core/pagination/base'
import { AxiosInstance } from 'axios'
import { Post, PostIdentifier } from '../types'

/** Index */
export interface PostIndexFilters {
  search?: string
  trashed?: FilterTrashed
}

interface PostIndexQuery
  extends DefaultIndexQueryContract<{
    filters: PostIndexFilters
  }> {}

type PostIndexQueryResult = PaginatedData<Post>

/** Store */
export interface PostStoreData {
  title: string
  content: string
}

interface PostStoreQuery extends DefaultStoreQueryContract<PostStoreData> {}

type PostStoreQueryResult = Post

interface PostsRepository {
  index(options: PostIndexQuery): Promise<PostIndexQueryResult>
  destroy(id: PostIdentifier): Promise<void>
}

class PostsApiRepository implements PostsRepository {
  private readonly axios: AxiosInstance

  constructor() {
    this.axios = useAxios()
  }

  async index(options: PostIndexQuery): Promise<PostIndexQueryResult> {
    const { data } = await sendAxiosGetRequest<PostIndexQueryResult>(this.axios, 'posts', options)

    return data
  }

  async store(options: PostStoreQuery): Promise<PostStoreQueryResult> {
    const { data } = await sendAxiosPostRequest<Post>(this.axios, 'posts', options)

    return data
  }

  async destroy(id: PostIdentifier): Promise<void> {
    await this.axios.delete(`posts/${id}`)
  }
}

export function usePostsRepository(): PostsRepository {
  return new PostsApiRepository()
}
