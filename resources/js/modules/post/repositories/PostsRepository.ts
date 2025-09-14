import { useAxios } from '@/composables/useAxios'
import { FilterTrashed } from '@/core/filters/trashed'
import { PaginatedData, PaginationComposable } from '@/core/pagination/base'
import { AxiosInstance, AxiosRequestConfig } from 'axios'
import { Post, PostIdentifier } from '../types'

export interface PostIndexFilters {
  search?: string
  trashed?: FilterTrashed
}

interface HasSignalContract {
  signal?: AbortSignal
}

interface HasParamsContract<T> {
  params: T
}

interface HasPaginationContract {
  pagination?: PaginationComposable
}

type OptionsContract = HasSignalContract | HasParamsContract<unknown> | HasPaginationContract

interface PostIndexQuery
  extends HasParamsContract<{
      filters: PostIndexFilters
    }>,
    HasSignalContract,
    HasPaginationContract {}

// interface PostIndexQueryResult {}

type ListResult = PaginatedData<Post>

interface PostsRepository {
  index(options: PostIndexQuery): Promise<ListResult>
  destroy(id: PostIdentifier): Promise<void>
}

function buildAxiosGetConfigFromOptions(options: OptionsContract, addDataToParams = false) {
  const config: AxiosRequestConfig = {}
  const data: any = {}

  if ('signal' in options) {
    config.signal = options.signal
  }

  if ('params' in options) {
    Object.assign(data, options.params)
  }

  if ('pagination' in options && options.pagination) {
    data.page = options.pagination.meta.current_page
    if (options.pagination.perPageAvailable) {
      data.per_page = options.pagination.meta.per_page
    }
  }

  if (addDataToParams) {
    config.params = data
  }

  return {
    config,
    data,
  }
}

function sendAxiosGetRequest<T>(axios: AxiosInstance, url: string, options: OptionsContract) {
  return axios.get<T>(url, buildAxiosGetConfigFromOptions(options, true).config)
}

class PostsApiRepository implements PostsRepository {
  private readonly api: AxiosInstance

  constructor() {
    this.api = useAxios()
  }

  async index(options: PostIndexQuery): Promise<ListResult> {
    const { data } = await sendAxiosGetRequest<ListResult>(this.api, 'posts', options)

    return data
  }

  async destroy(id: PostIdentifier): Promise<void> {
    await this.api.delete(`posts/${id}`)
  }
}

export function usePostsRepository(): PostsRepository {
  return new PostsApiRepository()
}
