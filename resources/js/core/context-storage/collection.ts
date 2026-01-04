import {
  ContextStorageHandler,
  ContextStorageHandlerConstructor,
} from '@/core/context-storage/handlers'

export type ContextStorageCollectionItem = {
  key: string
  handlers: ContextStorageHandler[]
  // isReady: boolean
}

interface ItemOptions {
  key: string
}

export class ContextStorageCollection {
  public active?: ContextStorageCollectionItem = undefined
  private collection: ContextStorageCollectionItem[] = []
  // private onReadyCallbacks: (() => void)[] = []
  private onActiveChangeCallbacks: ((item: ContextStorageCollectionItem) => void)[] = []

  constructor(private handlerConstructors: ContextStorageHandlerConstructor[]) {}

  // onReady(callback: () => void) {
  //   this.onReadyCallbacks.push(callback)
  // }

  onActiveChange(callback: (item: ContextStorageCollectionItem) => void) {
    this.onActiveChangeCallbacks.push(callback)
  }

  first() {
    return this.collection[0]
  }

  findItemByKey(key: string) {
    return this.collection.find((item) => item.key === key)
  }

  add(options: ItemOptions): ContextStorageCollectionItem {
    // Проблема скорее всего в том, что когда мы создаём handler, то router ещё не ready, нужно дождаться этого
    const handlers = this.handlerConstructors.map((constructor) => new constructor())

    const item: ContextStorageCollectionItem = { handlers, key: options.key }

    this.collection.push(item)

    return item
  }

  // markAsReady(readyItem: ContextStorageCollectionItem) {
  //   if (this.collection.indexOf(readyItem) === -1) {
  //     throw new Error('Item not found in collection')
  //   }
  //
  //   readyItem.isReady = true
  //
  //   if (this.collection.every((item) => item.isReady)) {
  //     this.onReadyCallbacks.forEach((callback) => callback())
  //     this.onReadyCallbacks = []
  //   }
  // }

  remove(removeItem: ContextStorageCollectionItem) {
    if (this.collection.indexOf(removeItem) === -1) {
      throw new Error('Item not found in collection')
    }

    this.collection = this.collection.filter((item) => item !== removeItem)

    //TODO: refactor - better determination of which storage to activate, maybe should passed as argument
    if (this.active === removeItem && this.collection.length > 0) {
      this.setActive(this.collection[this.collection.length - 1])
    }
  }

  setActive(activeItem: ContextStorageCollectionItem) {
    if (this.active === activeItem) {
      return
    }
    // console.log('called collection.setActive')
    // console.log('collection.setActive', activeItem)
    // debugger
    const hasActiveBefore = this.active !== undefined
    // console.log('set collection.active')
    this.active = activeItem

    this.collection.forEach((item) => {
      Object.values(item.handlers).forEach((handler) => {
        if (handler.setEnabled) {
          // console.log('calling handler.setEnabled', item === activeItem, handler, item)
          handler.setEnabled(item === activeItem, !hasActiveBefore)
        }
      })
    })

    this.onActiveChangeCallbacks.forEach((callback) => callback(activeItem))
  }
}
