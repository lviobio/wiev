<template>
  <NBreadcrumb>
    <NBreadcrumbItem v-for="breadcrumb in computedBreadcrumbs" :key="toValue(breadcrumb.title)">
      <RouterLink :to="breadcrumb.route">
        {{ breadcrumb.title }}
      </RouterLink>
    </NBreadcrumbItem>
  </NBreadcrumb>
</template>

<script setup lang="ts">
import { Breadcrumb } from '@/core/types'
import { computed } from 'vue'

const props = defineProps<{
  breadcrumbs: Breadcrumb[]
}>()

const computedBreadcrumbs = computed(() => {
  return [
    {
      title: 'Home',
      route: { name: 'home' },
    },
    ...props.breadcrumbs,
  ]
})
</script>
