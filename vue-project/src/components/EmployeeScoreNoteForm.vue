<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { 
  Star, 
  Save, 
  X,
  ChevronDown,
  User
} from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Dialog from '@/components/ui/Dialog.vue'
import { platformApi } from '@/api/laravel/platform'

const props = defineProps<{
  employeeId?: number
  showModal?: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved', note: any): void
}>()

const canAddNotes = ref(false)

interface Employee {
  id: number
  name: string
  department?: { name: string }
  designation?: { name: string }
}

const employees = ref<Employee[]>([])
const selectedEmployeeId = ref<number | null>(props.employeeId || null)
const isLoading = ref(false)
const isSaving = ref(false)
const showEmployeeDropdown = ref(false)
const selectedEmployee = ref<Employee | null>(null)

const noteForm = ref({
  note_type: 'adhoc',
  attendance_note: [{ rating: 3, comment: '' }],
  discipline_note: [{ rating: 3, comment: '' }],
  performance_note: [{ rating: 3, comment: '' }],
  general_note: '',
  score_adjustment: 0,
  period_start: '',
  period_end: ''
})

const ratingOptions = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]

const loadEmployees = async () => {
  isLoading.value = true
  try {
    const response = await platformApi.getDepartmentEmployeesForNotes() as any
    employees.value = response?.employees || []
    canAddNotes.value = true
    
    if (props.employeeId) {
      selectedEmployee.value = employees.value.find(e => e.id === props.employeeId) || null
    }
  } catch (e) {
    canAddNotes.value = false
    console.error('Failed to load employees', e)
  } finally {
    isLoading.value = false
  }
}

const selectEmployee = (employee: Employee) => {
  selectedEmployee.value = employee
  selectedEmployeeId.value = employee.id
  showEmployeeDropdown.value = false
}

const addRatingField = (type: 'attendance_note' | 'discipline_note' | 'performance_note') => {
  noteForm.value[type].push({ rating: 3, comment: '' })
}

const removeRatingField = (type: 'attendance_note' | 'discipline_note' | 'performance_note', index: number) => {
  if (noteForm.value[type].length > 1) {
    noteForm.value[type].splice(index, 1)
  }
}

const saveNote = async () => {
  if (!selectedEmployeeId.value) return

  isSaving.value = true
  try {
    const data = {
      employee_id: selectedEmployeeId.value,
      note_type: noteForm.value.note_type,
      attendance_note: noteForm.value.attendance_note,
      discipline_note: noteForm.value.discipline_note,
      performance_note: noteForm.value.performance_note,
      general_note: noteForm.value.general_note,
      score_adjustment: noteForm.value.score_adjustment,
      period_start: noteForm.value.period_start || undefined,
      period_end: noteForm.value.period_end || undefined
    }

    const response = await platformApi.createScoreNote(data)
    emit('saved', response)
    resetForm()
    emit('close')
  } catch (e) {
    console.error('Failed to save note', e)
  } finally {
    isSaving.value = false
  }
}

const resetForm = () => {
  noteForm.value = {
    note_type: 'adhoc',
    attendance_note: [{ rating: 3, comment: '' }],
    discipline_note: [{ rating: 3, comment: '' }],
    performance_note: [{ rating: 3, comment: '' }],
    general_note: '',
    score_adjustment: 0,
    period_start: '',
    period_end: ''
  }
  selectedEmployee.value = null
  selectedEmployeeId.value = props.employeeId || null
}

const getRatingLabel = (rating: number) => {
  const labels = ['', 'Very low', 'Low', 'Needs work', 'Fair', 'Solid', 'Good', 'Strong', 'Very strong', 'Excellent', 'Outstanding']
  return labels[rating] || ''
}

onMounted(() => {
  void loadEmployees()
})

watch(() => props.employeeId, (newId) => {
  if (newId) {
    selectedEmployeeId.value = newId
    selectedEmployee.value = employees.value.find(employee => employee.id === newId) || null
  }
})
</script>

<template>
  <Dialog
    :open="Boolean(showModal)"
    title="Add Employee Performance Note"
    description="Managers, HR, and team leads can record structured 1–10 notes that feed the employee score."
    size="2xl"
    @close="emit('close')"
  >
    <Card class="border-none bg-transparent shadow-none">
      <CardContent class="space-y-6 px-0 py-0">
        <p v-if="!canAddNotes" class="text-center text-slate-500 py-8">
          You don't have permission to add performance notes.
        </p>
        
        <template v-else>
          <div v-if="!props.employeeId" class="relative">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Select Employee
            </label>
            <button
              type="button"
              class="w-full flex items-center justify-between px-4 py-2 border rounded-lg bg-white dark:bg-slate-900"
              @click="showEmployeeDropdown = !showEmployeeDropdown"
            >
              <span v-if="selectedEmployee" class="flex items-center gap-2">
                <User class="h-4 w-4 text-slate-400" />
                {{ selectedEmployee.name }}
                <span class="text-xs text-slate-400">{{ selectedEmployee.department?.name }}</span>
              </span>
              <span v-else class="text-slate-400">Select an employee...</span>
              <ChevronDown class="h-4 w-4" />
            </button>
            
            <div v-if="showEmployeeDropdown" class="absolute z-10 w-full mt-1 border rounded-lg bg-white dark:bg-slate-900 shadow-lg max-h-60 overflow-y-auto">
              <button
                v-for="emp in employees"
                :key="emp.id"
                type="button"
                class="w-full flex items-center gap-2 px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-left"
                @click="selectEmployee(emp)"
              >
                <User class="h-4 w-4 text-slate-400" />
                <div>
                  <div class="font-medium">{{ emp.name }}</div>
                  <div class="text-xs text-slate-400">{{ emp.department?.name }} - {{ emp.designation?.name }}</div>
                </div>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Note Type
              </label>
              <select
                v-model="noteForm.note_type"
                class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-slate-900"
              >
                <option value="adhoc">Ad-hoc</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="annual">Annual</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Score Adjustment (-20 to +20)
              </label>
              <input
                v-model.number="noteForm.score_adjustment"
                type="number"
                min="-20"
                max="20"
                class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-slate-900"
              />
            </div>
          </div>

          <div class="space-y-4 pt-4 border-t">
            <h4 class="font-medium flex items-center gap-2">
              <Star class="h-4 w-4 text-amber-500" />
              Attendance Notes
            </h4>
            <p class="text-xs text-slate-500">Rate from 1 to 10. You can add a reason if you want.</p>
            <div v-for="(_, idx) in noteForm.attendance_note" :key="idx" class="flex items-start gap-2">
              <select
                v-model="noteForm.attendance_note[idx].rating"
                class="px-2 py-1 border rounded bg-white dark:bg-slate-900"
              >
                <option v-for="r in ratingOptions" :key="r" :value="r">{{ r }}/10 - {{ getRatingLabel(r) }}</option>
              </select>
              <input
                v-model="noteForm.attendance_note[idx].comment"
                type="text"
                placeholder="Reason for this attendance note..."
                class="flex-1 px-3 py-1 border rounded"
              />
              <Button variant="ghost" size="sm" @click="removeRatingField('attendance_note', idx)">
                <X class="h-4 w-4" />
              </Button>
            </div>
            <Button variant="outline" size="sm" @click="addRatingField('attendance_note')">
              + Add Attendance Note
            </Button>
          </div>

          <div class="space-y-4 pt-4 border-t">
            <h4 class="font-medium flex items-center gap-2">
              <Star class="h-4 w-4 text-amber-500" />
              Discipline Notes
            </h4>
            <p class="text-xs text-slate-500">Rate from 1 to 10. You can add a reason if you want.</p>
            <div v-for="(_, idx) in noteForm.discipline_note" :key="idx" class="flex items-start gap-2">
              <select
                v-model="noteForm.discipline_note[idx].rating"
                class="px-2 py-1 border rounded bg-white dark:bg-slate-900"
              >
                <option v-for="r in ratingOptions" :key="r" :value="r">{{ r }}/10 - {{ getRatingLabel(r) }}</option>
              </select>
              <input
                v-model="noteForm.discipline_note[idx].comment"
                type="text"
                placeholder="Reason for this discipline note..."
                class="flex-1 px-3 py-1 border rounded"
              />
              <Button variant="ghost" size="sm" @click="removeRatingField('discipline_note', idx)">
                <X class="h-4 w-4" />
              </Button>
            </div>
            <Button variant="outline" size="sm" @click="addRatingField('discipline_note')">
              + Add Discipline Note
            </Button>
          </div>

          <div class="space-y-4 pt-4 border-t">
            <h4 class="font-medium flex items-center gap-2">
              <Star class="h-4 w-4 text-amber-500" />
              Performance Notes
            </h4>
            <p class="text-xs text-slate-500">Rate from 1 to 10. You can add a reason if you want.</p>
            <div v-for="(_, idx) in noteForm.performance_note" :key="idx" class="flex items-start gap-2">
              <select
                v-model="noteForm.performance_note[idx].rating"
                class="px-2 py-1 border rounded bg-white dark:bg-slate-900"
              >
                <option v-for="r in ratingOptions" :key="r" :value="r">{{ r }}/10 - {{ getRatingLabel(r) }}</option>
              </select>
              <input
                v-model="noteForm.performance_note[idx].comment"
                type="text"
                placeholder="Reason for this performance note..."
                class="flex-1 px-3 py-1 border rounded"
              />
              <Button variant="ghost" size="sm" @click="removeRatingField('performance_note', idx)">
                <X class="h-4 w-4" />
              </Button>
            </div>
            <Button variant="outline" size="sm" @click="addRatingField('performance_note')">
              + Add Performance Note
            </Button>
          </div>

          <div class="space-y-2 pt-4 border-t">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              General Comment
            </label>
            <textarea
              v-model="noteForm.general_note"
              rows="3"
              placeholder="Overall observations and recommendations..."
              class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-slate-900"
            />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t">
            <Button variant="outline" @click="emit('close')">Cancel</Button>
            <Button 
              :disabled="!selectedEmployeeId || isSaving" 
              @click="saveNote"
            >
              <Save class="h-4 w-4 mr-2" />
              {{ isSaving ? 'Saving...' : 'Save Note' }}
            </Button>
          </div>
        </template>
      </CardContent>
    </Card>
  </Dialog>
</template>
