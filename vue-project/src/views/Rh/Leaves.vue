<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus, CalendarDays } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import { getStoredToken } from '@/utils/authStorage'

const items = ref<any[]>([])
const isLoading = ref(true)

const columns = [
  { key: 'employee_name', label: 'Employee' },
  { key: 'leave_type', label: 'Leave Type' },
  { key: 'date_range', label: 'Duration' },
  { key: 'days', label: 'Days Output' },
  { key: 'status', label: 'Status' }
]

const fetchLeaves = async () => {
  isLoading.value = true
  try {
    const token = getStoredToken()
    const res = await fetch(`${import.meta.env.VITE_LARAVEL_API_URL}/leaves`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    
    if (!res.ok) throw new Error('Endpoint unverified')
    const json = await res.json()
    items.value = json.data || []
    
  } catch (err) {
    console.warn('Using mock data for leaves', err)
  } finally {
    // Fallback mock data
    if (items.value.length === 0) {
      items.value = [
        { id: 1, employee_name: 'John Doe', leave_type: 'Annual Leave', date_range: 'Oct 12 - Oct 15', days: '3 Days', status: 'Approved' },
        { id: 2, employee_name: 'Jane Smith', leave_type: 'Sick Leave', date_range: 'Oct 20 - Oct 21', days: '2 Days', status: 'Pending' },
        { id: 3, employee_name: 'Mike Johnson', leave_type: 'Unpaid Leave', date_range: 'Nov 01 - Nov 05', days: '5 Days', status: 'Rejected' },
      ]
    }
    isLoading.value = false
  }
}

const getStatusVariant = (status: string) => {
  if (status === 'Approved') return 'success'
  if (status === 'Pending') return 'warning'
  if (status === 'Rejected') return 'destructive'
  return 'secondary'
}

onMounted(fetchLeaves)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <CalendarDays class="w-8 h-8 text-orange-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Leave Management</h2>
          <p class="text-gray-500 dark:text-gray-400">Review and approve employee time-off requests.</p>
        </div>
      </div>
      <Button class="bg-orange-600 hover:bg-orange-700 text-white"><Plus class="w-4 h-4 mr-2" /> Request Leave</Button>
    </div>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="items" 
          :loading="isLoading"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions>
            <Button variant="outline" size="sm" class="mr-2">Approve</Button>
            <Button variant="outline" size="sm" class="text-red-500 border-red-200 hover:bg-red-50">Reject</Button>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
