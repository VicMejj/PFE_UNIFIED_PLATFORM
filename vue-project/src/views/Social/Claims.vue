<script setup lang="ts">
import { ref } from 'vue'
import { FileHeart } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'

const items = ref([
  { id: 1, reference: 'SB-8012', employee_name: 'Sarah Connor', benefit: 'Home Office Equipment', amount: '$450.00', status: 'Reimbursed' },
  { id: 2, reference: 'SB-8013', employee_name: 'Tim Drake', benefit: 'Wellness Stipend', amount: '$120.00', status: 'Pending Review' },
  { id: 3, reference: 'SB-8014', employee_name: 'Anna Williams', benefit: 'Gym Membership', amount: '$45.00', status: 'Reimbursed' },
])

const columns = [
  { key: 'reference', label: 'Reference' },
  { key: 'employee_name', label: 'Employee' },
  { key: 'benefit', label: 'Benefit Type' },
  { key: 'amount', label: 'Reimbursement' },
  { key: 'status', label: 'Status' }
]

const getStatusVariant = (status: string) => {
  return status === 'Reimbursed' ? 'success' : 'warning'
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <FileHeart class="w-8 h-8 text-indigo-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Social Claims</h2>
          <p class="text-gray-500 dark:text-gray-400">Process perk and wellness reimbursement requests.</p>
        </div>
      </div>
    </div>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="items" 
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions>
            <Button variant="outline" size="sm">Review</Button>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
