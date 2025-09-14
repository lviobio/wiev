<template>
  <section class="bg-gray-50">
    <div class="mx-auto flex min-h-screen flex-col items-center justify-center px-6 py-8">
      <AppLogo class="mb-6" />
      <div class="w-full rounded-lg bg-white shadow sm:max-w-md">
        <RouterView />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue'
</script>
