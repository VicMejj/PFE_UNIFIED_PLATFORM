<script setup lang="ts">
</script>

<template>
  <div class="p-8">
    <h1 class="text-3xl font-bold mb-6">RH Manager Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">Employees</div>
      <div class="card">Contracts</div>
      <div class="card">Insurances</div>
      <div class="card">Reports</div>
    </div>
  </div>
</template>

<style scoped>
.card {
  @apply bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition;
}
</style>