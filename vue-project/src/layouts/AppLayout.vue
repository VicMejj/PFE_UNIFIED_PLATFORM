<script setup lang="ts">
import { ref } from 'vue'
import Sidebar from '@/components/common/Sidebar.vue'
import TopBar from '@/components/common/TopBar.vue'

const sidebarOpen = ref(true)

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
const closeSidebar = () => {
  if (window.innerWidth < 1024) sidebarOpen.value = false
}
</script>

<template>
  <div class="min-h-screen bg-transparent">
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <div
      :class="['relative transition-all duration-300', sidebarOpen ? 'lg:ml-[280px]' : 'ml-0']"
    >
      <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-96 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.08),_transparent_70%)] dark:bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.08),_transparent_70%)]"></div>

      <TopBar @toggle-sidebar="toggleSidebar" />

      <main class="relative z-10 px-4 pb-8 pt-6 sm:px-6 lg:px-8">
        <RouterView />
      </main>
    </div>

    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="closeSidebar"
    ></div>
  </div>
</template>
