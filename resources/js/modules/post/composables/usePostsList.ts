import { usePagination } from '@/core/pagination/base'
import { createEmptyObjectFromSchema } from '@/core/utils/form-schemas'
import { ListContextData, makeContextData } from '@/modules/post/composables/usePostListData'
import {
  PostListFilters,
  postListFiltersSchema,
  usePostRepository,
} from '@/modules/post/repositories/PostRepository'
import { Post } from '@/modules/post/types'
import { watchDebounced } from '@vueuse/core'
import { ref } from 'vue'

interface UseListComposable {
  // context: Reactive<ListContextData<PostListFilters>>
  context: Ref<ListContextData<PostListFilters>>
}

export function usePostsList(options?: UseListComposable) {
  const context = makeContextData()
  // const context = options?.context ?? makeContextData()

  const items = ref<Post[]>([])
  const loading = ref(false)
  const filters = ref(createEmptyObjectFromSchema(postListFiltersSchema))
  // const filters = toRef(context, 'filters')
  // const filters = computed(() => context.value.filters)
  let abortController: AbortController

  const repository = usePostRepository()

  const pagination = usePagination()

  // watch(
  //   () => castAsPage(pagination.state.value)?.page,
  //   (newValue) => {
  //     context.value.page = newValue
  //   },
  // )
  // watch(
  //   () => context.value.page,
  //   (newValue) => {
  //     pagination.setPage(newValue)
  //   },
  // )
  //
  // watch(
  //   () => castAsPage(pagination.state.value)?.per_page,
  //   (newValue) => {
  //     context.value.per_page = newValue
  //   },
  // )
  // watch(
  //   () => context.value.per_page,
  //   (newValue) => {
  //     pagination.setPerPage(newValue)
  //   },
  // )
  //
  // watch(
  //   filters,
  //   (newValue) => {
  //     context.value.filters = newValue
  //   },
  //   { deep: true },
  // )
  // watch(
  //   () => context.value.filters,
  //   (newValue) => {
  //     filters.value = newValue
  //   },
  //   { deep: true },
  // )

  // const paginationPage = toRef(pagination.state, 'page')

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
    context,
    repository,
  }
}
