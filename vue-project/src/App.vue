<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <header>
    <div class="wrapper">
      <div class="app-header">
        <h1>HR Management System</h1>
      </div>
      
      <nav>
        <RouterLink to="/">Dashboard</RouterLink>
        <!-- Add more navigation links as you create other views -->
      </nav>
    </div>
  </header>

  <main>
    <RouterView />
  </main>
</template>

<style scoped>
header {
  @apply bg-white shadow-md;
  line-height: 1.5;
}

.wrapper {
  @apply container mx-auto px-4 py-4;
}

.app-header {
  @apply mb-4;
}

.app-header h1 {
  @apply text-2xl font-bold text-gray-800;
}

nav {
  @apply flex gap-4;
  width: 100%;
  font-size: 14px;
}

nav a {
  @apply px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100 transition-colors;
}

nav a.router-link-exact-active {
  @apply bg-blue-100 text-blue-700 font-medium;
}

@media (min-width: 1024px) {
  .wrapper {
    @apply flex items-center justify-between;
  }
  
  .app-header {
    @apply mb-0;
  }
  
  nav {
    @apply gap-6;
    font-size: 1rem;
  }
}
</style>