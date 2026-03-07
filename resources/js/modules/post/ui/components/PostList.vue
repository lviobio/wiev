<script setup lang="tsx">
import { trashedOptions } from '@/core/filters/trashed'
import {
  actionGroup,
  dateColumn,
  defineColumns,
  defineFilters,
  deleteAction,
  linkColumn,
  makeDataHandlerFromRepository,
  openAction,
  useListPage,
  type UseListParams,
} from '@/core/list-page'
import { z } from 'zod'
import { postListFiltersSchema, usePostRepository } from '../../repositories/PostRepository'
import { postRouteNames } from '../../router/names'
import { Post } from '../../types'

type Filters = z.infer<typeof postListFiltersSchema>

const props = defineProps<{
  callback?: (params: UseListParams<Filters>) => void
}>()

const repository = usePostRepository()

const ListPage = useListPage<Post, Filters>({
  dataHandler: makeDataHandlerFromRepository(repository),
  filtersSchema: postListFiltersSchema,

  filters: defineFilters(postListFiltersSchema, {
    title: { placeholder: 'Search by title' },
    trashed: { options: trashedOptions },
    // created_at — auto-inferred as DateRangeFilter
  }),

  columns: defineColumns<Post>([
    linkColumn('id', {
      width: 50,
      to: (row) => ({ name: postRouteNames.show, params: { id: row.id } }),
      windowed: (row) => ({ title: `Post #${row.id}` }),
    }),
    { title: 'Title', key: 'title', sorter: true },
    { title: 'Content', key: 'content', ellipsis: { tooltip: true } },
    dateColumn('created_at', { width: 200, sorter: true }),
  ]),

  actions: [
    actionGroup([
      openAction((row) => ({ name: postRouteNames.show, params: { id: row.id } })),
      deleteAction((row) => repository.delete(row.id), {
        confirm: 'Delete this post?',
        success: (row) => `Post ${row.id} deleted successfully`,
      }),
    ]),
  ],

  search: { placeholder: 'Search' },
})

props.callback?.(ListPage.params)
ListPage.actions.enableWatchers()
</script>

<template>
  <ListPage.Component />
</template>
