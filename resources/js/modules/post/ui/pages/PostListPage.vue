<script setup lang="ts">
import { useListContextSync } from '@/core/list-context/useListContextSync'
import { postListDataSchema, usePostListContext } from '@/modules/post/composables/usePostListData'
import { List } from '@/modules/post/ui/components'
import { useContextStorage } from 'vue-context-storage'

const context = usePostListContext()
context.init()
const contextData = context.get()

useContextStorage('query', contextData, {
  schema: postListDataSchema,
  onlyChanges: false,
  additionalDefaultData: {
    page: 1,
    per_page: 15,
  },
})
</script>

<template>
  <NFlex>
    <List.Component :callback="(params) => useListContextSync(context.get(), params)" />
  </NFlex>
</template>

<style scoped></style>
