import { ModuleInterface } from '@/modules/_shared/interface'
import { postRouteGenerator } from '@/modules/post/router/names'
import Icon from './icon'

export const postModule: ModuleInterface = {
  icon: Icon,
  routeGenerator: postRouteGenerator,
}
