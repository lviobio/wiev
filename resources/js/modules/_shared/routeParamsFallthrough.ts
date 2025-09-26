import RouterParamsBinder from '@/modules/_shared/RouterParamsBinder.vue'
import { Component } from 'vue'

export function routeParamsFallthrough(loader: () => Promise<Component>) {
  return h(RouterParamsBinder, (scope: any) => h(defineAsyncComponent(loader), scope))
}
