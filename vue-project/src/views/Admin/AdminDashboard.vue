<template>
  <div class="p-6">
    <h1 class="title">Admin Dashboard</h1>

    <!-- 👇 grid stays HERE -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">Employees</div>
      <div class="card">Contracts</div>
      <div class="card">Insurances</div>
      <div class="card">Users</div>
    </div>
  </div>
</template>

<style scoped>
.title {
  @apply text-3xl font-bold mb-6;
}

.card {
  @apply bg-white rounded-xl shadow p-6 text-center font-semibold;
}
</style>