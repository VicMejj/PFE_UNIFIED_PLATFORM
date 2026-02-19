<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'

const router = useRouter()

const user = computed(() => {
  const stored = localStorage.getItem('user')
  return stored ? JSON.parse(stored) : null
})

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}
</script>

<template>
  <header v-if="user">
    <div class="wrapper">
      <div class="app-header">
        <h1>HR Management System</h1>
      </div>

      <nav>
        <!-- COMMON -->
        <RouterLink to="/">Dashboard</RouterLink>

        <!-- ADMIN -->
        <RouterLink v-if="user.role === 'admin'" to="/admin">
          Admin
        </RouterLink>

        <!-- RH MANAGER -->
        <RouterLink v-if="user.role === 'rh_manager'" to="/rh">
          RH
        </RouterLink>

        <!-- EMPLOYEE -->
        <RouterLink v-if="user.role === 'employee'" to="/employee">
          My Space
        </RouterLink>

        <button @click="logout" class="logout">Logout</button>
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
}

.wrapper {
  @apply container mx-auto px-4 py-4 flex items-center justify-between;
}

nav {
  @apply flex gap-4 items-center;
}

nav a {
  @apply px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100;
}

nav a.router-link-exact-active {
  @apply bg-blue-100 text-blue-700 font-medium;
}

.logout {
  @apply px-3 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200;
}
</style>