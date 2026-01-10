import 'vue-router'

declare module 'vue-router' {
  interface RouteLocationOptions {
    subRouterContextKey?: string
  }
  interface Router {
    subRouterContextKey?: string
    sub?: true
    // callWithSubRouterContextKey<T>(contextKey?: string, fn: () => T): T
  }
  interface RouteMeta {
    //TODO: add callback support, to verify resolved route
    subRouterRoot?: boolean
  }
}
