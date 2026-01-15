import 'vue-router'

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
  interface ImportMetaEnv {
    readonly VITE_APP_NAME: string

    [key: string]: string | boolean | undefined
  }

  interface ImportMeta {
    readonly env: ImportMetaEnv
    readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>
  }
}

export interface WindowOptions {
  title?: string | ((params: any) => string)
}

declare module 'vue-router' {
  interface RouteLocationOptions {
    title?: string | ((params: any) => string)
    windowed?: boolean | WindowOptions
  }
  interface Router {
    contextKey?: string
    custom?: true
  }
  interface RouteMeta {
    //TODO: add callback support, to verify resolved route
    windowRoot?: boolean
  }
  // interface Router {
  //   pushable(location: RouteLocationOptions): (ParamsOf<typeof location.name>) => Router['push']
  // }
}
