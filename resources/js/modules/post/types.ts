import { DateTimeNullableType } from '@/core/types'

export type PostIdentifier = number

export interface Post {
  id: PostIdentifier
  title: string
  content: string | null
  cover?: string
  published_at: DateTimeNullableType
  created_at: DateTimeNullableType
  updated_at: DateTimeNullableType
}

/** Files attached to a post (App\Modules\Post\Actions\Files) */
export type PostFileIdentifier = string

export interface PostFile {
  uuid: PostFileIdentifier
  name: string
  file_name: string
  mime_type: string | null
  size: number
  url: string
  created_at: DateTimeNullableType
  updated_at: DateTimeNullableType
}

// export interface PostListData {
//   page: number
//   per_page: number
//   filters: PostListFilters
// }
//
// export interface PostListData {
//   page: number
//   per_page: number
//   filters: PostListFilters
// }
