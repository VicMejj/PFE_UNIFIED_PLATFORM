<template>
  <div class="p-6">
    <!-- Title -->
    <h1 class="text-2xl font-bold mb-6">
      Dashboard
    </h1>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <p class="card-title">Employees</p>
        <p class="card-value">{{ stats.employees }}</p>
      </div>

      <div class="card">
        <p class="card-title">Contracts</p>
        <p class="card-value">{{ stats.contracts }}</p>
      </div>

      <div class="card">
        <p class="card-title">Social Benefits</p>
        <p class="card-value">{{ stats.benefits }}</p>
      </div>

      <div class="card">
        <p class="card-title">Insurance Policies</p>
        <p class="card-value">{{ stats.policies }}</p>
      </div>
    </div>

    <!-- Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Activity -->
      <div class="panel">
        <h2 class="panel-title">Recent Activity</h2>
        <ul class="space-y-2">
          <li v-for="(item, index) in recentActivity" :key="index">
            • {{ item }}
          </li>
        </ul>
      </div>

      <!-- System Status -->
      <div class="panel">
        <h2 class="panel-title">System Status</h2>
        <ul class="space-y-2">
          <li>🟢 Django API: Online</li>
          <li>🟢 Laravel API: Online</li>
          <li>🟢 Database: Connected</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'

interface DashboardStats {
  employees: number
  contracts: number
  benefits: number
  policies: number
}

const stats = reactive<DashboardStats>({
  employees: 128,
  contracts: 87,
  benefits: 45,
  policies: 62
})

const recentActivity: string[] = [
  'New employee added',
  'Contract updated',
  'Insurance policy approved',
  'Social benefit request validated'
]
</script>

<style scoped>
.card {
  @apply bg-white rounded-lg shadow p-4;
}

.card-title {
  @apply text-sm text-gray-500;
}

.card-value {
  @apply text-2xl font-bold mt-2;
}

.panel {
  @apply bg-white rounded-lg shadow p-4;
}

.panel-title {
  @apply text-lg font-semibold mb-3;
}
</style>
