import { BuildMap, FlattenRoutes } from '@/core/navigator/types'
import { createRoutes } from '@/router/routes'

// Build types from app-level and module route arrays (extendable union)
type AppRoutes = ReturnType<typeof createRoutes>

export type RouteParamMap = BuildMap<FlattenRoutes<AppRoutes>>
