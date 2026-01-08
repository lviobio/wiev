import { DesktopContextManagerInterface } from '@/core/navigator/contextManager'
import { desktopContextKeySymbol, desktopContextManagerSymbol } from '@/core/symbols'

export const desktopContextManager: InjectionKey<DesktopContextManagerInterface> =
  desktopContextManagerSymbol
export const desktopContextKey: InjectionKey<string> = desktopContextKeySymbol
