<script setup lang="ts">
import { DateTimeNullableType } from '@/core/types'

const props = defineProps<{ value: DateTimeNullableType }>()
</script>

<template>
  <div v-if="props.value !== null">{{ new Date(props.value).toLocaleString() }}</div>
  <div v-else class="text-neutral-500 italic">No date</div>
</template>

<style scoped></style>
