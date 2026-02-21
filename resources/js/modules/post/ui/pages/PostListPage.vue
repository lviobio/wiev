<script setup lang="ts">
import { ListContextSymbol } from '@/core/crud/list-page/types'
import { postListDataSchema, usePostListContext } from '@/modules/post/composables/usePostListData'
import { List } from '@/modules/post/ui/components'
import { useContextStorage } from 'vue-context-storage'

const context = usePostListContext()
context.init()
const contextData = context.get()

useContextStorage('query', contextData, {
  schema: postListDataSchema,
  onlyChanges: true,
  additionalDefaultData: {
    page: 1,
    per_page: 15,
  },
})

provide(ListContextSymbol, context)
</script>

<template>
  <div>
    <List.Component />
  </div>
</template>

<style scoped></style>
