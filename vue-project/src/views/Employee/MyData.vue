<script setup lang="ts">
</script>

<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-6">My Information</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">My Contracts</div>
      <div class="card">My Insurance</div>
    </div>
  </div>
</template>

<style scoped>
.card {
  @apply bg-white rounded-xl shadow-md p-6;
}
</style>