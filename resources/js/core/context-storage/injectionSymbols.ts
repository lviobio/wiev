import {
  ContextStorageCollection,
  ContextStorageCollectionItem,
} from '@/core/context-storage/collection'
import { ContextStorageQueryHandler } from '@/core/context-storage/handlers/query'
import {
  collection,
  collectionItem,
  contextStorageQueryHandler,
  handlers,
} from '@/core/context-storage/symbols'
import { InjectionKey } from 'vue'

export const contextStorageCollectionInjectKey: InjectionKey<ContextStorageCollection> = collection
export const contextStorageCollectionItemInjectKey: InjectionKey<ContextStorageCollectionItem> =
  collectionItem
export const contextStorageHandlersInjectKey: InjectionKey<
  ContextStorageCollectionItem['handlers']
> = handlers

export const contextStorageQueryHandlerInjectKey: InjectionKey<
  InstanceType<typeof ContextStorageQueryHandler>
> = contextStorageQueryHandler
