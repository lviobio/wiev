<script setup lang="tsx">
import { trashedOptions } from '@/core/filters/trashed'
import { useNaiveUiPagination } from '@/core/pagination/naive-ui'
import { usePostsList } from '../../composables/usePostsList'
import { Post } from '../../types'
import {
  DataTableColumns,
  DataTableInst,
  InputInst,
  NA,
  NButton,
  NFlex,
  NInput,
  NPopconfirm,
  useMessage,
} from 'naive-ui'
import AppDateTime from '@/components/AppDateTime.vue'
import { useAppNavigator } from '@/core/navigator/useAppNavigator'
import { postRouteNames } from '@/modules/post/router/names'
import { TableFilter } from '@/components/AppDataTable.vue'
import { Search24Regular } from '@vicons/fluent'

const message = useMessage()
const appNavigator = useAppNavigator()

const { items, loading, load, reload, filters, pagination, repository } = usePostsList()

const dataTablePagination = useNaiveUiPagination(pagination)

const openPostWindow = appNavigator.delayedNavigate({
  name: postRouteNames.show,
  title: (p) => `Post #${p.id}`,
  windowed: true,
})

const columns = [
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
    filter: true,
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
] satisfies DataTableColumns<Post>

const tableRef = useTemplateRef<DataTableInst>('tableRef')

const tableFilters = [
  {
    key: 'title',
    title: 'Title',
    getIndicator() {
      return `Title: ${filters.value.title}`
    },
    active: computed(() => {
      return filters.value.title !== null
    }),
    reset() {
      filters.value.title = null
    },
    make({ after }) {
      const originalValue = computed(() => filters.value.title)
      const localValue = ref(originalValue.value)

      function confirm() {
        filters.value.title = localValue.value

        after()
      }

      function clear() {
        filters.value.title = null

        after()
      }

      return {
        input: () => {
          const inputRef = ref<InputInst>()

          nextTick(() => {
            inputRef.value?.focus()
          })

          return (
            <NInput
              ref={inputRef}
              onKeyup={(e) => {
                if (e.key === 'Enter') {
                  confirm()
                }
              }}
              value={localValue.value}
              onUpdate:value={(newValue) => (localValue.value = newValue || null)}
              autofocus
              clearable
            />
          )
        },
        confirm,
        clear,
      }
    },
  },
] satisfies TableFilter[]

load()
</script>

<template>
  <NFlex>
    <AppDataTable
      ref="tableRef"
      :columns="columns"
      :data="items"
      :loading="loading"
      size="small"
      :pagination="dataTablePagination"
      :filters="tableFilters"
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
