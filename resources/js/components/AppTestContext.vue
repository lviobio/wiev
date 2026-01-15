<script setup lang="ts">
import { usePostListContext } from '@/modules/post/composables/usePostListData'
import { postRouteGenerator } from '@/modules/post/router/names'
import { Search24Regular } from '@vicons/fluent'
import { cloneDeep, get, isEqual } from 'lodash'
import { transform } from 'vue-context-storage'

const context = usePostListContext()
context.init()
const contextData = context.get()

const filters = contextData.filters

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
    }
  },
  { immediate: true },
)

watch(
  () => cloneDeep(data.value.filters),
  (filters, oldFilters) => {
    if (isEqual(filters, oldFilters)) {
      return
    }
    console.log('pushing post list page', filters)
    router.push({
      query: {
        'filters[search]': filters.search ?? undefined,
        'filters[title]': filters.title ?? undefined,
      },
    })
  },
  {
    deep: true,
  },
)

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
      <p>
        router.contextKey: {{ router.contextKey }} | $router.contextKey: {{ $router.contextKey }}
      </p>
      <p>$route.name: {{ $route.name }}</p>
      <p>$route.query: {{ $route.query }}</p>
    </div>
    <NButton
      @click="$router.push({ ...postRouteGenerator.index(), query: { 'filters[title]': 'asd' } })"
      >Test1</NButton
    >
    <NButton @click="$router.push({ query: { 'filters[title]': 'zxc' } })">Test2</NButton>
    <NButton @click="redirectDelayed">redirectDelayed</NButton>
    <NGrid :x-gap="12" :y-gap="12" :cols="3">
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
  </NFlex>
</template>

<style scoped></style>
