import {
  DefaultCreateQueryContract,
  DefaultCreateQueryResultContract,
  DefaultUpdateQueryContract,
  DefaultUpdateQueryResultContract,
  HasSignalContract,
  sendAxiosPostRequest,
} from '@/core/api/simple-repository-helpers-v1/main'
import { AxiosInstance } from 'axios'
import { PostFile, PostFileIdentifier, PostIdentifier } from '../types'

/** List */
type PostFileListResult = { data: PostFile[] }

/** Attach */
type PostFileAttachQuery = DefaultCreateQueryContract<{ file: File }>
type PostFileAttachResult = DefaultCreateQueryResultContract<PostFile>

/** Rename */
type PostFileRenameQuery = DefaultUpdateQueryContract<{ name: string }>
type PostFileRenameResult = DefaultUpdateQueryResultContract<PostFile>

export interface PostFileRepository {
  list(postId: PostIdentifier, options?: HasSignalContract): Promise<PostFileListResult>
  attach(postId: PostIdentifier, options: PostFileAttachQuery): Promise<PostFileAttachResult>
  rename(
    postId: PostIdentifier,
    fileId: PostFileIdentifier,
    options: PostFileRenameQuery,
  ): Promise<PostFileRenameResult>
  detach(postId: PostIdentifier, fileId: PostFileIdentifier): Promise<void>
  /** Downloads the file and saves it in the browser under its display name. */
  download(postId: PostIdentifier, file: PostFile): Promise<void>
}

class PostFileApiRepository implements PostFileRepository {
  private readonly axios: AxiosInstance

  constructor() {
    this.axios = useAxios()
  }

  async list(postId: PostIdentifier, options?: HasSignalContract) {
    const { data } = await this.axios.get<PostFileListResult>(`posts/${postId}/files`, {
      signal: options?.signal,
    })

    return data
  }

  async attach(postId: PostIdentifier, options: PostFileAttachQuery) {
    const { data } = await sendAxiosPostRequest<PostFileAttachResult>(
      this.axios,
      `posts/${postId}/files`,
      options,
    )

    return data
  }

  async rename(postId: PostIdentifier, fileId: PostFileIdentifier, options: PostFileRenameQuery) {
    const { data } = await this.axios.patch<PostFileRenameResult>(
      `posts/${postId}/files/${fileId}`,
      options.data,
      { signal: options.signal },
    )

    return data
  }

  async detach(postId: PostIdentifier, fileId: PostFileIdentifier) {
    await this.axios.delete(`posts/${postId}/files/${fileId}`)
  }

  async download(postId: PostIdentifier, file: PostFile) {
    const response = await this.axios.get<Blob>(`posts/${postId}/files/${file.uuid}/download`, {
      responseType: 'blob',
    })

    const url = URL.createObjectURL(response.data)

    try {
      const link = document.createElement('a')
      link.href = url
      link.download = file.file_name
      link.click()
    } finally {
      URL.revokeObjectURL(url)
    }
  }
}

export function usePostFileRepository(): PostFileRepository {
  return new PostFileApiRepository()
}
