import { useBasePagination } from '@/core/pagination/base'
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

  const load = async () => {
    abortController?.abort()
    abortController = new AbortController()
    loading.value = true

    return repository
      .list({
        data: {
          filters: filters.value,
        },
        pagination,
        signal: abortController.signal,
      })
      .then((response) => {
        items.value = response.data
        pagination.setMeta(response.meta)
      })
      .finally(() => {
        loading.value = false
      })
  }

  async function reload() {
    await load()
  }

  const pagination = useBasePagination({
    withPerPageSelect: true,
    onPageChange: load,
  })

  watchDebounced(filters, () => pagination.setPage(1), {
    deep: true,
    debounce: 400,
  })

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
