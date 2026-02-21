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
