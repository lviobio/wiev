<script setup lang="ts">
import { useContextStorageQueryHandler } from '@/core/context-storage/handlers/query'
import { transform } from '@/core/context-storage/handlers/query-transform-helpers'
import { FilterTrashedValues } from '@/core/filters/trashed'
import { usePostListContext } from '@/modules/post/composables/usePostListData'
import { List } from '@/modules/post/ui/components'
import { get } from 'lodash'

const context = usePostListContext()
context.init()
const contextData = context.get()

const data = ref({
  filters: contextData.filters,
})

useContextStorageQueryHandler(data, {
  transform: (deserialized) => {
    return {
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
})
</script>

<template>
  <NFlex>
    <List.Component />
  </NFlex>
</template>

<style scoped></style>
