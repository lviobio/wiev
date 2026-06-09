<script setup lang="tsx">
import { makeDataHandlerFromRepositoryAdapter, useListPage } from '@/core/crud/list-page'
import { postListFiltersSchema, usePostRepository } from '../../repositories/PostRepository'
import { postRouteNames } from '../../router/names'

const repository = usePostRepository()

const ListPage = useListPage(
  makeDataHandlerFromRepositoryAdapter(repository.list.bind(repository)),
  {
    filtersSchema: postListFiltersSchema,

    columns: ({ column, linkColumn, dateColumn }) => [
      linkColumn('id', {
        width: 50,
        to: (row) => ({ name: postRouteNames.show, params: { id: row.id } }),
        windowed: (row) => ({ title: `Post #${row.id}` }),
      }),
      column('title', { sorter: true }),
      column('content', { ellipsis: { tooltip: true } }),
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
  },
)
</script>

<template>
  <div>
    <ListPage.Component />
  </div>
</template>
