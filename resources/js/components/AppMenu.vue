<template>
  <NMenu
    v-model:value="activeKey"
    :collapsed="collapsed"
    :collapsed-width="64"
    :collapsed-icon-size="24"
    :options="menuOptions"
  />
</template>

<script setup lang="tsx">
import { Component, h, ref, watch } from 'vue'
import { MenuOption, NIcon } from 'naive-ui'
import { Home24Filled, SignOut24Filled } from '@vicons/fluent'
import { RouteLocationRaw, RouterLink, useRoute } from 'vue-router'
import { postModule } from '@/modules/post'

defineProps<{
  collapsed: boolean
}>()

const route = useRoute()

const activeKey = ref<string | null>(null)

const setActiveKey = () => {
  activeKey.value = String(route.name)
}

watch(() => route.name, setActiveKey)
setActiveKey()

const renderLabel = (text: string, route: RouteLocationRaw) => {
  return () => <RouterLink to={route}>{text}</RouterLink>
}

const renderIcon = (icon: Component) => {
  return () => <NIcon>{h(icon)}</NIcon>
}

const menuOptions: MenuOption[] = [
  {
    label: renderLabel('Home', { name: 'home' }),
    key: 'home',
    icon: renderIcon(Home24Filled),
  },
  {
    label: renderLabel('Posts', postModule.routeGenerator.index()),
    key: 'posts',
    icon: renderIcon(postModule.icon),
  },
  {
    label: renderLabel('Logout', { name: 'logout' }),
    key: 'logout',
    icon: renderIcon(SignOut24Filled),
  },
]
</script>
