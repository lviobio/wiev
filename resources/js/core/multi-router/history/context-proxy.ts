import type { RouterHistory } from 'vue-router'
import type { MultiRouterHistoryManager } from './manager'
import type { HistoryLocation, HistoryState, NavigationCallback } from './types'

export class ContextHistoryProxy implements RouterHistory {
  constructor(
    private contextKey: string,
    private manager: MultiRouterHistoryManager,
  ) {}

  get base(): string {
    return this.manager.base
  }

  get location(): HistoryLocation {
    return this.manager.getContextLocation(this.contextKey)
  }

  get state(): HistoryState {
    return this.manager.getContextState(this.contextKey)
  }

  push(to: HistoryLocation, data?: HistoryState): void {
    this.manager.push(this.contextKey, to, data)
  }

  replace(to: HistoryLocation, data?: HistoryState): void {
    this.manager.replace(this.contextKey, to, data)
  }

  go(delta: number, triggerListeners?: boolean): void {
    this.manager.go(this.contextKey, delta, triggerListeners)
  }

  listen(callback: NavigationCallback): () => void {
    return this.manager.listen(this.contextKey, callback)
  }

  createHref(location: HistoryLocation): string {
    return this.manager.createHref(location)
  }

  destroy(): void {
    this.manager.removeContextHistory(this.contextKey)
  }
}
