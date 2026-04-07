<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { coreApi } from '@/api/laravel/core'
import { unwrapItems } from '@/api/http'

const roles = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const roleForm = reactive({
  name: '',
  description: ''
})

const columns = [
  { key: 'name', label: 'Role Name' },
  { key: 'description', label: 'Description' }
]

const fetchRoles = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await coreApi.getRoles()
    roles.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch roles', err)
    errorMessage.value = 'Unable to load roles.'
  } finally {
    isLoading.value = false
  }
}

const createRole = async () => {
  isCreating.value = true
  feedback.value = ''
  errorMessage.value = ''

  try {
    await coreApi.createRole({
      name: roleForm.name,
      description: roleForm.description
    })

    feedback.value = 'Role created successfully.'
    roleForm.name = ''
    roleForm.description = ''
    await fetchRoles()
  } catch (err: any) {
    console.error('Failed to create role', err)
    errorMessage.value = err.response?.data?.message || 'Unable to create role.'
  } finally {
    isCreating.value = false
  }
}

const deleteRole = async (roleId: number) => {
  if (!window.confirm('Are you sure you want to delete this role?')) {
    return
  }

  errorMessage.value = ''
  try {
    await coreApi.deleteRole(roleId)
    feedback.value = 'Role deleted successfully.'
    await fetchRoles()
  } catch (err) {
    console.error('Failed to delete role', err)
    errorMessage.value = 'Unable to delete role.'
  }
}

onMounted(fetchRoles)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Roles Management</h2>
        <p class="text-gray-500 dark:text-gray-400">Create and manage user roles and permissions.</p>
      </div>
      <Button class="bg-blue-600 hover:bg-blue-700 text-white" @click="isCreating = !isCreating">
        <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Create Role' }}
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- CREATE ROLE FORM -->
    <Card v-if="isCreating">
      <CardHeader>
        <CardTitle>Create New Role</CardTitle>
        <CardDescription>Add a new role to the system.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2 md:col-span-2">
          <Label>Role Name</Label>
          <Input v-model="roleForm.name" placeholder="e.g., Project Manager" />
        </div>
        <div class="space-y-2 md:col-span-2">
          <Label>Description</Label>
          <Input v-model="roleForm.description" placeholder="Role description and responsibilities" />
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button :disabled="isCreating" @click="createRole">Create Role</Button>
        </div>
      </CardContent>
    </Card>

    <!-- ROLES TABLE -->
    <Card>
      <CardHeader>
        <CardTitle>All Roles</CardTitle>
        <CardDescription>View and manage all system roles.</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <div v-if="isLoading" class="text-center py-8">
          <p class="text-gray-500">Loading roles...</p>
        </div>
        <div v-else-if="roles.length === 0" class="text-center py-8">
          <p class="text-gray-500">No roles found.</p>
        </div>
        <DataTable 
          v-else
          :columns="columns" 
          :data="roles" 
          :loading="isLoading"
          searchPlaceholder="Search roles..."
          emptyMessage="No roles found."
        >
          <template #actions="{ item }">
            <Button size="sm" variant="destructive" @click="deleteRole(item.id)">Delete</Button>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
