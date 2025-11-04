import { format } from 'date-fns'
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

export function useDateFormatters() {
  function formatDate(date: Date | number) {
    return format(date, 'yyyy-MM-dd')
  }

  function formatDateTime(date: Date | number) {
    return format(date, 'yyyy-MM-dd HH:mm')
  }

  return {
    formatDate,
    formatDateTime,
  }
}
