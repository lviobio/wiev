import { Ref } from 'vue'
import { RouteLocation } from 'vue-router'

export type DateTimeType = number
export type DateTimeNullableType = DateTimeType | null

export interface User {
  id: number
  email: string
}

export interface Breadcrumb {
  title: string | Ref<string>
  route: RouteLocation | { name: string }
}

export interface PageExpose {
  title: string | Ref<string>
  breadcrumbs: null | Breadcrumb[] | Ref<Breadcrumb[]> | Ref<null>
}
