<script setup lang="ts">
const emit = defineEmits(['clear', 'confirm'])
</script>

<template>
  <NCard size="small">
    <slot />
    <template #action>
      <div class="flex justify-end gap-2">
        <NButton size="tiny" @click="emit('clear')">
          <span>Clear</span>
        </NButton>
        <NButton size="tiny" type="primary" @click="emit('confirm')">
          <span>Confirm</span>
        </NButton>
      </div>
    </template>
  </NCard>
</template>

<style scoped></style>
