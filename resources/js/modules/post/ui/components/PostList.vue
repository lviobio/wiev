<script setup lang="tsx">
import { trashedOptions } from '@/core/filters/trashed'
import { useNaiveUiPagination } from '@/core/pagination/naive-ui'
import { usePostsList } from '../../composables/usePostsList'
import { Post } from '../../types'
import {
  DataTableColumns,
  DataTableInst,
  NA,
  NButton,
  NFlex,
  NPopconfirm,
  useMessage,
} from 'naive-ui'
import AppDateTime from '@/components/AppDateTime.vue'
import { useAppNavigator } from '@/core/navigator/useAppNavigator'
import { postRouteNames } from '@/modules/post/router/names'
import { Search24Regular } from '@vicons/fluent'
import {
  DateRangeFilter,
  makeDataTableFiltering,
  TextFilter,
} from '@/components/AppDataTable/filters'

const message = useMessage()
const appNavigator = useAppNavigator()

const { items, loading, load, reload, filters, pagination, repository } = usePostsList()

const dataTablePagination = useNaiveUiPagination(pagination)

const openPostWindow = appNavigator.delayedNavigate({
  name: postRouteNames.show,
  title: (p) => `Post #${p.id}`,
  windowed: true,
})

const columns: DataTableColumns<Post> = [
  {
    title: 'ID',
    key: 'id',
    width: 50,
    render(row) {
      // open in a window-like modal instead of navigating
      return (
        <span onClick={() => openPostWindow({ id: row.id })}>
          <NA>{row.id}</NA>
        </span>
      )
    },
  },
  {
    title: 'Title',
    key: 'title',
  },
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
    filter: true,
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
              appNavigator.navigate({
                name: postRouteNames.show,
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

const tableRef = useTemplateRef<DataTableInst>('tableRef')

const filtering = makeDataTableFiltering(filters, [
  TextFilter.make('title', 'Title').withPlaceholder('Search by title').toTableFilter(),
  DateRangeFilter.make('created_at', 'Created').toTableFilter(),
])
</script>

<template>
  <NFlex>
    <AppDataTable
      ref="tableRef"
      :columns="columns"
      :data="items"
      :loading="loading"
      :loader="load"
      size="small"
      :pagination="dataTablePagination"
      :filtering="filtering"
      remote
      striped
    >
      <template #header>
        <NGrid :x-gap="12" :y-gap="12" :cols="3">
          <NGi>
            <NSelect
              v-model:value="filters.trashed"
              placeholder="Without Trashed"
              :options="trashedOptions"
              clearable
            />
          </NGi>
          <NGi span="1">
            <NInput
              :value="filters.title"
              @update:value="filters.title = $event || null"
              placeholder="Title"
              clearable
            />
          </NGi>
          <NGi span="1">
            <NInput
              :value="filters.search"
              @update:value="filters.search = $event || null"
              placeholder="Search"
              clearable
            >
              <template #prefix>
                <NIcon><Search24Regular /></NIcon>
              </template>
            </NInput>
          </NGi>
        </NGrid>
      </template>
    </AppDataTable>
  </NFlex>
</template>

<style scoped></style>
