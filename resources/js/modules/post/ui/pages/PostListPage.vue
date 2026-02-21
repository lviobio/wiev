<script setup lang="ts">
import { FilterTrashedValues } from '@/core/filters/trashed'
import { usePostListContext } from '@/modules/post/composables/usePostListData'
import { List } from '@/modules/post/ui/components'
import { get } from 'lodash'
import { transform, useContextStorage } from 'vue-context-storage'

const context = usePostListContext()
context.init()
const contextData = context.get()

useContextStorage('query', contextData, {
  transform: (deserialized) => {
    return {
      page: transform.asNumber(get(deserialized, 'page'), { missable: true }),
      cursor: transform.asString(get(deserialized, 'cursor'), { missable: true }),
      per_page: transform.asNumber(get(deserialized, 'per_page'), { fallbackValue: 13 }),
      search: transform.asString(get(deserialized, 'search'), { missable: true }),
      filters: {
        search: transform.asString(get(deserialized, 'filters.search'), { nullable: true }),
        title: transform.asString(get(deserialized, 'filters.title'), { nullable: true }),
        created_at: {
          from: transform.asNumber(get(deserialized, 'filters.created_at.from'), {
            nullable: true,
          }),
          to: transform.asNumber(get(deserialized, 'filters.created_at.to'), { nullable: true }),
        },
        trashed: transform.asString(get(deserialized, 'filters.trashed'), {
          nullable: true,
          allowedValues: FilterTrashedValues,
        }),
      },
    }
  },
  onlyChanges: false,
  // additionalDefaultData: {
  //   page: 1,
  //   per_page: 15,
  // },
})
</script>

<template>
  <NFlex>
    <List.Component />
  </NFlex>
</template>

<style scoped></style>
