<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { attendanceApi } from '@/api/laravel/attendance'
import { unwrapItems } from '@/api/http'

const records = ref<any[]>([])
const stats = ref<any>(null)
const isLoading = ref(true)
const isRecording = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const attendanceForm = reactive({
  employee_id: '',
  date: new Date().toISOString().split('T')[0],
  check_in: '09:00',
  check_out: '17:00',
  status: 'present',
  notes: ''
})

const columns = [
  { key: 'employee_id', label: 'Employee ID' },
  { key: 'date', label: 'Date' },
  { key: 'check_in', label: 'Check In' },
  { key: 'check_out', label: 'Check Out' },
  { key: 'status', label: 'Status' }
]

const fetchAttendanceRecords = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await attendanceApi.getAttendanceRecords()
    records.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch attendance records', err)
    errorMessage.value = 'Unable to load attendance records.'
  } finally {
    isLoading.value = false
  }
}

const fetchStatistics = async () => {
  errorMessage.value = ''
  try {
    const response = await attendanceApi.getAttendanceStatistics()
    stats.value = (response as any).data || response
  } catch (err) {
    console.error('Failed to fetch statistics', err)
  }
}

const recordAttendance = async () => {
  isRecording.value = true
  feedback.value = ''
  errorMessage.value = ''

  try {
    await attendanceApi.createAttendanceRecord({
      employee_id: Number(attendanceForm.employee_id),
      date: attendanceForm.date,
      check_in: attendanceForm.check_in,
      check_out: attendanceForm.check_out,
      status: attendanceForm.status,
      notes: attendanceForm.notes
    })

    feedback.value = 'Attendance recorded successfully.'
    attendanceForm.employee_id = ''
    attendanceForm.notes = ''
    await fetchAttendanceRecords()
  } catch (err) {
    console.error('Failed to record attendance', err)
    errorMessage.value = 'Unable to record attendance.'
  } finally {
    isRecording.value = false
  }
}

onMounted(() => {
  fetchAttendanceRecords()
  fetchStatistics()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Attendance Tracking</h2>
        <p class="text-gray-500 dark:text-gray-400">Record and manage employee attendance.</p>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- STATISTICS -->
    <div v-if="stats" class="grid gap-4 md:grid-cols-4">
      <Card>
        <CardContent class="pt-6">
          <div class="text-center">
            <p class="text-sm text-gray-500">Present Today</p>
            <p class="text-3xl font-bold">{{ stats.present_today || 0 }}</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="pt-6">
          <div class="text-center">
            <p class="text-sm text-gray-500">Absent Today</p>
            <p class="text-3xl font-bold">{{ stats.absent_today || 0 }}</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="pt-6">
          <div class="text-center">
            <p class="text-sm text-gray-500">Late Today</p>
            <p class="text-3xl font-bold">{{ stats.late_today || 0 }}</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="pt-6">
          <div class="text-center">
            <p class="text-sm text-gray-500">On Leave Today</p>
            <p class="text-3xl font-bold">{{ stats.on_leave_today || 0 }}</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- ATTENDANCE FORM -->
    <Card>
      <CardHeader>
        <CardTitle>Record Attendance</CardTitle>
        <CardDescription>Mark employee attendance for the day.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="space-y-2">
          <Label>Employee ID</Label>
          <Input v-model="attendanceForm.employee_id" type="number" placeholder="Employee ID" />
        </div>
        <div class="space-y-2">
          <Label>Date</Label>
          <Input v-model="attendanceForm.date" type="date" />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="attendanceForm.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="late">Late</option>
            <option value="on-leave">On Leave</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Check In Time</Label>
          <Input v-model="attendanceForm.check_in" type="time" />
        </div>
        <div class="space-y-2">
          <Label>Check Out Time</Label>
          <Input v-model="attendanceForm.check_out" type="time" />
        </div>
        <div class="space-y-2 md:col-span-2 xl:col-span-3">
          <Label>Notes</Label>
          <Input v-model="attendanceForm.notes" placeholder="Optional notes" />
        </div>
        <div class="md:col-span-2 xl:col-span-3 flex justify-end">
          <Button :disabled="isRecording" @click="recordAttendance">Record Attendance</Button>
        </div>
      </CardContent>
    </Card>

    <!-- ATTENDANCE RECORDS -->
    <Card>
      <CardHeader>
        <CardTitle>Attendance Records</CardTitle>
        <CardDescription>View all recorded attendance.</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="records" 
          :loading="isLoading"
          searchPlaceholder="Search records..."
          emptyMessage="No attendance records found."
        />
      </CardContent>
    </Card>
  </div>
</template>
