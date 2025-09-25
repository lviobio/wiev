import { Component } from 'vue'
import { RouteLocationRaw } from 'vue-router'

export type ModuleRouteGenerator = Record<
  string,
  (...args: any[]) => RouteLocationRaw | ModuleRouteGenerator
>

export interface ModuleInterface {
  icon: Component
  routeGenerator: ModuleRouteGenerator
}
