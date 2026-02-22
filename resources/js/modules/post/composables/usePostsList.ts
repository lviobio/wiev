import { usePagination } from '@/core/pagination/base'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import {
  postListFiltersSchema,
  usePostRepository,
} from '@/modules/post/repositories/PostRepository'
import { Post } from '@/modules/post/types'
import { watchDebounced } from '@vueuse/core'
import { ref } from 'vue'

export function usePostsList() {
  const items = ref<Post[]>([])
  const loading = ref(false)
  const filters = ref(createEmptyObjectFromSchema(postListFiltersSchema))
  let abortController: AbortController

  const repository = usePostRepository()

  const pagination = usePagination()

  const makeQuery = () => {
    // debugger
    return {
      data: {
        filters: filters.value,
      },
      pagination,
      signal: abortController.signal,
    }
  }

  let isLoadScheduled = false
  const load = () => {
    console.log('called load')
    if (isLoadScheduled) {
      return
    }
    isLoadScheduled = true

    abortController?.abort()
    abortController = new AbortController()
    loading.value = true

    return repository
      .list(makeQuery())
      .then((response) => {
        items.value = response.data
        pagination.applyMeta(response.meta)
      })
      .finally(() => {
        loading.value = false
        isLoadScheduled = false
      })
  }

  async function reload() {
    await load()
  }

  watchDebounced(
    filters,
    () => {
      pagination.resetPage()
      console.log('filters changed')
      load()
    },
    {
      deep: true,
      debounce: 400,
    },
  )

  watch(
    () => JSON.stringify(pagination.queryParams.value),
    () => {
      console.log('pagination.queryParams changed')
      load()
    },
  )

  return {
    items,
    filters,
    loading,
    load,
    reload,
    pagination,
    repository,
  }
}
