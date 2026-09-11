import {
  DefaultCreateQueryContract,
  DefaultCreateQueryResultContract,
  sendAxiosPostRequest,
} from '@/core/api/simple-repository-helpers-v1/main'
import { DateTimeNullableType } from '@/core/types'
import { AxiosInstance } from 'axios'

/**
 * A file staged ahead of time, referenced by `uuid` wherever a module's own form
 * needs a file (e.g. `PostRepository`'s `cover`) - see the module's own repository for
 * how the identifier gets used, and the backend's `App\Core\Upload` module for how it
 * gets resolved back into a domain value.
 */
export interface TemporaryUpload {
  uuid: string
  original_name: string
  mime_type: string | null
  size: number
  created_at: DateTimeNullableType
}

type UploadQuery = DefaultCreateQueryContract<{ file: File }>
type UploadResult = DefaultCreateQueryResultContract<TemporaryUpload>

export interface TemporaryUploadRepository {
  upload(options: UploadQuery): Promise<UploadResult>
}

class TemporaryUploadApiRepository implements TemporaryUploadRepository {
  private readonly axios: AxiosInstance

  constructor() {
    this.axios = useAxios()
  }

  async upload(options: UploadQuery) {
    const { data } = await sendAxiosPostRequest<UploadResult>(this.axios, 'uploads', options)

    return data
  }
}

export function useTemporaryUploadRepository(): TemporaryUploadRepository {
  return new TemporaryUploadApiRepository()
}
