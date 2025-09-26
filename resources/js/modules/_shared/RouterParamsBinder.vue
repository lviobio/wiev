<script setup lang="ts">
const route = useRoute()
</script>

<template>
  <slot v-bind="{ params: route.params }" />
</template>

<style scoped></style>
