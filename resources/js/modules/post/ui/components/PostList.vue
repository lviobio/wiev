<script setup lang="tsx">
import { trashedOptions } from '@/core/filters/trashed'
import { useNaiveUiPagination } from '@/core/pagination/naive-ui'
import { usePostsList } from '../../composables/usePostsList'
import { Post } from '../../types'
import { DataTableColumns, NA, NButton, NDataTable, NFlex, NPopconfirm, useMessage } from 'naive-ui'
import AppDateTime from '@/components/AppDateTime.vue'
import { useDesktopWindows } from '@/core/windows/useDesktopWindows'
import { Show } from '@/modules/post/ui'
import { useAppNavigator } from '@/core/navigator/useAppNavigator'

const message = useMessage()
const appNavigator = useAppNavigator()
const windows = useDesktopWindows()

const { items, loading, load, reload, filters, pagination, repository } = usePostsList()

const dataTablePagination = useNaiveUiPagination(pagination)

const openPostWindow = windows.openable({
  title: (p) => `Post #${p.id}`,
  component: Show.Page,
  width: 600,
})

const columns: DataTableColumns<Post> = [
  {
    title: 'ID',
    key: 'id',
    width: 80,
    render(row) {
      // open in a window-like modal instead of navigating
      return (
        <span
          onClick={() =>
            openPostWindow({
              id: row.id,
            })
          }
        >
          <NA>{row.id}</NA>
        </span>
      )
    },
  },
  { title: 'Title', key: 'title' },
  {
    title: 'Content',
    key: 'content',
    ellipsis: { tooltip: true },
    render(row) {
      return row.content ?? ''
    },
  },
  {
    title: 'Created',
    key: 'created_at',
    width: 200,
    render(row) {
      return <AppDateTime value={row.created_at} />
    },
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 200,
    render(row) {
      return (
        <NFlex>
          <NButton
            size="small"
            type="info"
            onClick={() =>
              appNavigator.openOrNavigate({
                component: Show.Page,
                params: { id: row.id },
              })
            }
          >
            Open
          </NButton>
          <NPopconfirm
            onPositiveClick={() =>
              repository.delete(row.id).then(() => {
                reload()
                message.success(`Post ${row.id} deleted successfully`)
              })
            }
          >
            {{
              default: () => 'Delete this post?',
              trigger: () => (
                <NButton size="small" type="error">
                  Delete
                </NButton>
              ),
            }}
          </NPopconfirm>
        </NFlex>
      )
    },
  },
]

load()
</script>

<template>
  <NFlex>
    <NGrid :x-gap="12" :cols="3">
      <NGi span="2">
        <NInput v-model:value="filters.search" placeholder="Search" />
      </NGi>
      <NGi>
        <NSelect
          v-model:value="filters.trashed"
          placeholder="Without Trashed"
          :options="trashedOptions"
          clearable
        />
      </NGi>
    </NGrid>
    <NDataTable
      :columns="columns"
      :data="items"
      :loading="loading"
      size="small"
      :pagination="dataTablePagination"
      remote
      striped
    />
  </NFlex>
</template>

<style scoped></style>
