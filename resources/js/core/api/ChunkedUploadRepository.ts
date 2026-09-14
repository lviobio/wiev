import {
  DefaultCreateQueryContract,
  DefaultCreateQueryResultContract,
  HasSignalContract,
  sendAxiosPostRequest,
  sendAxiosPostRequestJson,
} from '@/core/api/simple-repository-helpers-v1/main'
import { TemporaryUpload } from '@/core/api/TemporaryUploadRepository'
import { DateTimeNullableType } from '@/core/types'
import { AxiosInstance } from 'axios'

/**
 * A file being staged in pieces - see App\Core\Upload\Actions\Chunked on the backend.
 * Ends in a {@see TemporaryUpload}, referenced the same way everywhere else in the
 * app once `complete()` succeeds - this shape only exists for the upload itself.
 */
export interface ChunkedUpload {
  uuid: string
  original_name: string
  mime_type: string | null
  total_size: number
  received_bytes: number
  created_at: DateTimeNullableType
}

type StartQuery = DefaultCreateQueryContract<{
  originalName: string
  mimeType: string | null
  totalSize: number
}>
type ChunkedUploadResult = DefaultCreateQueryResultContract<ChunkedUpload>

// A File, not a bare Blob (File.slice() alone) - buildFormData() (see
// simple-repository-helpers-v1) only special-cases `instanceof File`, so a raw Blob
// would get recursed into as a plain object instead of sent as form data.
type ChunkQuery = DefaultCreateQueryContract<{ chunk: File; offset: number }>

export interface ChunkedUploadRepository {
  start(options: StartQuery): Promise<ChunkedUploadResult>
  appendChunk(uuid: string, options: ChunkQuery): Promise<ChunkedUploadResult>
  status(uuid: string, options?: HasSignalContract): Promise<ChunkedUploadResult>
  complete(uuid: string): Promise<DefaultCreateQueryResultContract<TemporaryUpload>>
  abort(uuid: string): Promise<void>
}

class ChunkedUploadApiRepository implements ChunkedUploadRepository {
  private readonly axios: AxiosInstance

  constructor() {
    this.axios = useAxios()
  }

  async start(options: StartQuery) {
    const { data } = await sendAxiosPostRequestJson<ChunkedUploadResult>(
      this.axios,
      'uploads/chunked',
      options,
    )

    return data
  }

  async appendChunk(uuid: string, options: ChunkQuery) {
    const { data } = await sendAxiosPostRequest<ChunkedUploadResult>(
      this.axios,
      `uploads/chunked/${uuid}/chunks`,
      options,
    )

    return data
  }

  async status(uuid: string, options?: HasSignalContract) {
    const { data } = await this.axios.get<ChunkedUploadResult>(`uploads/chunked/${uuid}`, {
      signal: options?.signal,
    })

    return data
  }

  async complete(uuid: string) {
    const { data } = await this.axios.post<DefaultCreateQueryResultContract<TemporaryUpload>>(
      `uploads/chunked/${uuid}/complete`,
    )

    return data
  }

  async abort(uuid: string) {
    await this.axios.delete(`uploads/chunked/${uuid}`)
  }
}

export function useChunkedUploadRepository(): ChunkedUploadRepository {
  return new ChunkedUploadApiRepository()
}
