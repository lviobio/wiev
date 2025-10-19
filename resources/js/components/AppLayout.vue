<template>
  <NSpace vertical>
    <NLayout has-sider>
      <NLayoutSider
        class="min-h-screen !bg-gray-50"
        bordered
        collapse-mode="width"
        :collapsed-width="64"
        :width="240"
        :collapsed="collapsed"
        show-trigger
        @collapse="collapsed = true"
        @expand="collapsed = false"
      >
        <AppLogo class="my-4 justify-center text-xl" :collapsed="collapsed" />
        <AppMenu :collapsed="collapsed" />
      </NLayoutSider>
      <NLayout>
        <RouterView v-slot="{ Component }">
          <div class="flex flex-col gap-4 px-4 py-4">
            <AppBreadcrumbs
              v-if="currentComponent && currentComponent.breadcrumbs"
              :breadcrumbs="currentComponent.breadcrumbs"
            />

            <AppSuspense>
              <component :is="Component" ref="currentComponent" />
            </AppSuspense>
          </div>
        </RouterView>
      </NLayout>
    </NLayout>
  </NSpace>
</template>

<script setup lang="tsx">
import AppBreadcrumbs from '@/components/AppBreadcrumbs.vue'
import AppLogo from '@/components/AppLogo.vue'
import AppMenu from '@/components/AppMenu.vue'
import { useAuthStore } from '@/composables/useAuthStore'
import { useUserStore } from '@/composables/useUserStore'
import { PageExpose } from '@/core/types'
import { useStorage } from '@vueuse/core'
import { onMounted, ref, watch } from 'vue'

const authStore = useAuthStore()
const userStore = useUserStore()

const collapsed = useStorage('menu-collapsed', false)
const currentComponent = ref()

watch(currentComponent, (component: PageExpose | null) => {
  document.title = component
    ? `${component.title} - ${import.meta.env.VITE_APP_NAME}`
    : import.meta.env.VITE_APP_NAME
})

onMounted(() => {
  if (authStore.loggedIn.value) {
    userStore.getCurrentUser()
  }
})
</script>
