import { useBasePagination } from '@/core/pagination/base'
import { PostIndexFilters, usePostsRepository } from '@/modules/post/repositories/PostsRepository'
import { Post } from '@/modules/post/types'
import { watchDebounced } from '@vueuse/core'
import { ref } from 'vue'

export function usePostsList() {
  const items = ref<Post[]>([])
  const loading = ref(false)
  const filters = ref<PostIndexFilters>({
    search: '',
    trashed: null,
  })
  let abortController: AbortController

  const repository = usePostsRepository()

  const load = async () => {
    abortController?.abort()
    abortController = new AbortController()
    loading.value = true

    return repository
      .index({
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
