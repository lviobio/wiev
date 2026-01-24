import type { RouterHistory } from 'vue-router'

export type HistoryLocation = string
export type HistoryState = Record<string, any>
export type HistoryBuilder = () => RouterHistory

export enum NavigationType {
  pop = 'pop',
  push = 'push',
}

export enum NavigationDirection {
  back = 'back',
  forward = 'forward',
  unknown = '',
}

export interface NavigationInformation {
  type: NavigationType
  direction: NavigationDirection
  delta: number
}

export interface NavigationCallback {
  (to: HistoryLocation, from: HistoryLocation, information: NavigationInformation): void
}

export interface VirtualStackEntry {
  location: HistoryLocation
  state: HistoryState
}

export interface VirtualStack {
  entries: VirtualStackEntry[]
  position: number
}

export interface ContextHistoryState {
  virtualStack: VirtualStack
  listeners: Set<NavigationCallback>
  historyEnabled: boolean
}
