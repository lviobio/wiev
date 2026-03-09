<script setup lang="tsx">
import {
  dateColumn,
  linkColumn,
  makeDataHandlerFromRepository,
  useListPage,
  type UseListParams,
} from '@/core/list-page'
import { z } from 'zod'
import { postListFiltersSchema, usePostRepository } from '../../repositories/PostRepository'
import { postRouteNames } from '../../router/names'

type Filters = z.infer<typeof postListFiltersSchema>

const props = defineProps<{
  callback?: (params: UseListParams<Filters>) => void
}>()

const repository = usePostRepository()

const ListPage = useListPage({
  dataHandler: makeDataHandlerFromRepository(repository),
  filtersSchema: postListFiltersSchema,

  columns: [
    linkColumn('id', {
      width: 50,
      to: (row) => ({ name: postRouteNames.show, params: { id: row.id } }),
      windowed: (row) => ({ title: `Post #${row.id}` }),
    }),
    { title: 'Title', key: 'title', sorter: true },
    { title: 'Content', key: 'content', ellipsis: { tooltip: true } },
    dateColumn('created_at', { width: 200, sorter: true }),
  ],

  actions: [
    {
      type: 'group',
      actions: [
        { type: 'open', to: (row) => ({ name: postRouteNames.show, params: { id: row.id } }) },
        {
          type: 'delete',
          handler: (row) => repository.delete(row.id),
          confirm: 'Delete this post?',
          success: (row) => `Post ${row.id} deleted successfully`,
        },
      ],
    },
  ],
})

props.callback?.(ListPage.params)
ListPage.actions.enableWatchers()
</script>

<template>
  <ListPage.Component />
</template>
