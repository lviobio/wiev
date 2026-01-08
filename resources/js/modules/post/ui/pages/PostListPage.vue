<script setup lang="ts">
import { FilterTrashedValues } from '@/core/filters/trashed'
import { usePostListContext } from '@/modules/post/composables/usePostListData'
import { List } from '@/modules/post/ui/components'
import { get } from 'lodash'
import { transform, useContextStorageQueryHandler } from 'vue-context-storage'

const context = usePostListContext()
context.init()
const contextData = context.get()

const data = ref({
  filters: contextData.filters,
})

const router = useRouter()

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

const redirectDelayed = () => {
  setTimeout(() => {
    router.push(postRouteGenerator.show(1))
  }, 1500)
}
// console.log(router.currentRoute.value)
// console.log(router.resolve({ name: 'posts.index' }))
</script>

<template>
  <NFlex>
    <List.Component />
  </NFlex>
</template>

<style scoped></style>
