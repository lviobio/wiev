import { MultiRouterManagerInstance } from '@/core/multi-router/contextManager'
import { RouterHistory } from 'vue-router'

export class MultiRouterHistoryManager {
  private items: Map<string, RouterHistory> = new Map()

  constructor(private manager: MultiRouterManagerInstance) {}
}

