import { useChunkedUploadRepository } from '@/core/api/ChunkedUploadRepository'
import { TemporaryUpload, useTemporaryUploadRepository } from '@/core/api/TemporaryUploadRepository'
import axios from 'axios'

/** Below this size, staging goes through the single-shot /uploads request unchanged. */
const CHUNK_THRESHOLD = 15 * 1024 * 1024
/** Per-request chunk size once a file needs chunking at all. */
const CHUNK_SIZE = 8 * 1024 * 1024

export interface ChunkedUploadOptions {
  onProgress?: (percent: number) => void
  signal?: AbortSignal
}

export interface ChunkedUpload {
  /** Stages `file` ahead of time, returning the same TemporaryUpload shape either upload path produces. */
  upload(file: File, options?: ChunkedUploadOptions): Promise<TemporaryUpload>
}

/**
 * Splits a large file into pieces before staging it, so it isn't bound by a single
 * request's own upload limit - see App\Core\Upload\Actions\Chunked on the backend,
 * which this mirrors one call at a time (start -> chunks... -> complete).
 */
export function useChunkedUpload(): ChunkedUpload {
  const uploads = useTemporaryUploadRepository()
  const chunked = useChunkedUploadRepository()

  async function upload(file: File, options: ChunkedUploadOptions = {}): Promise<TemporaryUpload> {
    if (file.size <= CHUNK_THRESHOLD) {
      const { data } = await uploads.upload({ data: { file }, signal: options.signal })
      options.onProgress?.(100)
      return data
    }

    return uploadInChunks(file, options)
  }

  async function uploadInChunks(file: File, options: ChunkedUploadOptions): Promise<TemporaryUpload> {
    const { data: session } = await chunked.start({
      data: {
        originalName: file.name,
        mimeType: file.type || null,
        totalSize: file.size,
      },
      signal: options.signal,
    })

    try {
      let offset = session.received_bytes

      while (offset < file.size) {
        // buildFormData() (simple-repository-helpers-v1) only special-cases
        // `instanceof File`, not `Blob` - a bare File.slice() result would otherwise
        // get recursed into as a plain object (its methods enumerated as fields)
        // instead of sent as form data.
        const slice = new File([file.slice(offset, offset + CHUNK_SIZE)], file.name, {
          type: file.type,
        })

        try {
          const { data } = await chunked.appendChunk(session.uuid, {
            data: { chunk: slice, offset },
            signal: options.signal,
          })
          offset = data.received_bytes
        } catch (error) {
          // A dropped response can make a retry present a now-stale offset - the
          // server always answers with the real one to resync from, so this loop
          // just picks up from there instead of restarting the whole upload. Note:
          // useAxios()'s shared interceptor still surfaces this as a toast (it has
          // no way to know this particular 409 is expected, recoverable noise) -
          // acceptable since a genuine mismatch should be rare in normal use.
          const resync = receivedBytesFromOffsetMismatch(error)
          if (resync === null) {
            throw error
          }
          offset = resync
          continue
        }

        options.onProgress?.(Math.round((offset / file.size) * 100))
      }

      const { data } = await chunked.complete(session.uuid)
      options.onProgress?.(100)
      return data
    } catch (error) {
      // Best-effort: release the session rather than leaving it for
      // uploads:prune-chunked to reap on its own schedule. The original error is
      // what the caller needs to see either way.
      void chunked.abort(session.uuid).catch(() => undefined)
      throw error
    }
  }

  return { upload }
}

function receivedBytesFromOffsetMismatch(error: unknown): number | null {
  // useAxios()'s interceptor wraps every rejection in its own exception type,
  // preserving the real AxiosError as `.cause` (see core/errors/axios.ts) - that's
  // where the response body this needs actually lives.
  const cause = error instanceof Error ? error.cause : undefined

  if (!axios.isAxiosError(cause) || cause.response?.status !== 409) {
    return null
  }

  const receivedBytes = cause.response.data?.received_bytes

  return typeof receivedBytes === 'number' ? receivedBytes : null
}
