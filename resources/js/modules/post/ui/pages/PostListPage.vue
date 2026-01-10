<script setup lang="ts">
import { FilterTrashedValues } from '@/core/filters/trashed'
import { usePostListContext } from '@/modules/post/composables/usePostListData'
import { postRouteGenerator } from '@/modules/post/router/names'
import { List } from '@/modules/post/ui/components'
import { get } from 'lodash'
import { transform } from 'vue-context-storage'

const context = usePostListContext()
context.init()
const contextData = context.get()

const data = ref({
  filters: contextData.filters,
})

const router = useRouter()
const route = useRoute()

watch(
  () => route.query,
  (query) => {
    data.value.filters = {
      search: transform.asString(get(query, 'filters[search]'), { nullable: true }),
      title: transform.asString(get(query, 'filters[title]'), { nullable: true }),
      created_at: {
        from: transform.asNumber(get(query, 'filters[created_at][from]'), {
          nullable: true,
        }),
        to: transform.asNumber(get(query, 'filters[created_at][to]'), { nullable: true }),
      },
      trashed: transform.asString(get(query, 'filters[trashed]'), {
        nullable: true,
        allowedValues: FilterTrashedValues,
      }),
    }
  },
  { immediate: true },
)

watch(
  () => data.value.filters,
  (filters) => {
    // console.log('pushing post list page', filters)
    router.push({
      query: {
        'filters[search]': filters.search ?? undefined,
        'filters[title]': filters.title ?? undefined,
        'filters[created_at][from]': filters.created_at.from ?? undefined,
        'filters[created_at][to]': filters.created_at.to ?? undefined,
        'filters[trashed]': filters.trashed ?? undefined,
      },
    })
  },
  {
    deep: true,
  },
)

// useContextStorageQueryHandler(data, {
//   transform: (deserialized) => {
//     return {
//       filters: {
//         search: transform.asString(get(deserialized, 'filters.search'), { nullable: true }),
//         title: transform.asString(get(deserialized, 'filters.title'), { nullable: true }),
//         created_at: {
//           from: transform.asNumber(get(deserialized, 'filters.created_at.from'), {
//             nullable: true,
//           }),
//           to: transform.asNumber(get(deserialized, 'filters.created_at.to'), { nullable: true }),
//         },
//         trashed: transform.asString(get(deserialized, 'filters.trashed'), {
//           nullable: true,
//           allowedValues: FilterTrashedValues,
//         }),
//       },
//     }
//   },
// })

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
    <div>
      <p>{{ router.contextKey }}</p>
      <p>{{ $route.name }}</p>
    </div>
    <div>
      <p>{{ $route.query }}</p>
    </div>
    <NButton
      @click="$router.push({ ...postRouteGenerator.index(), query: { 'filters[title]': 'asd' } })"
      >Test1</NButton
    >
    <NButton @click="$router.push({ query: { 'filters[title]': 'zxc' } })">Test2</NButton>
    <NButton @click="redirectDelayed">redirectDelayed</NButton>
    <List.Component />
  </NFlex>
</template>

<style scoped></style>
