import { UploadFileInfo } from 'naive-ui'

export function formatUrlToUploadFileInfo(url?: string | null): UploadFileInfo | undefined {
  if (!url) {
    return undefined
  }

  return {
    id: url + Math.random(),
    name: url.split('/').pop() ?? '',
    status: 'finished',
    url,
  }
}
