<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Badge from '@/components/ui/Badge.vue'
import { coreApi } from '@/api/laravel/core'
import { unwrapItems } from '@/api/http'

const users = ref<any[]>([])
const roles = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const isActionLoading = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const userForm = reactive({
  name: '',
  email: '',
  password: '',
  status: 'active'
})

const getStatusVariant = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'active': return 'success'
    case 'inactive': return 'destructive'
    case 'suspended': return 'warning'
    case 'banned': return 'destructive'
    default: return 'secondary'
  }
}

const fetchUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await coreApi.getUsers()
    users.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch users', err)
    errorMessage.value = 'Unable to load users.'
  } finally {
    isLoading.value = false
  }
}

const fetchRoles = async () => {
  errorMessage.value = ''
  try {
    const response = await coreApi.getRoles()
    roles.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch roles', err)
  }
}

const createUser = async () => {
  isCreating.value = true
  feedback.value = ''
  errorMessage.value = ''

  try {
    await coreApi.createUser({
      name: userForm.name,
      email: userForm.email,
      password: userForm.password,
      status: userForm.status
    })

    feedback.value = 'User created successfully.'
    userForm.name = ''
    userForm.email = ''
    userForm.password = ''
    userForm.status = 'active'
    await fetchUsers()
  } catch (err: any) {
    console.error('Failed to create user', err)
    errorMessage.value = err.response?.data?.message || 'Unable to create user.'
  } finally {
    isCreating.value = false
  }
}

const suspendUser = async (userId: number) => {
  isActionLoading.value = true
  errorMessage.value = ''

  try {
    await coreApi.suspendUser(userId)
    feedback.value = 'User suspended successfully.'
    await fetchUsers()
  } catch (err) {
    console.error('Failed to suspend user', err)
    errorMessage.value = 'Unable to suspend user.'
  } finally {
    isActionLoading.value = false
  }
}

const activateUser = async (userId: number) => {
  isActionLoading.value = true
  errorMessage.value = ''

  try {
    await coreApi.activateUser(userId)
    feedback.value = 'User activated successfully.'
    await fetchUsers()
  } catch (err) {
    console.error('Failed to activate user', err)
    errorMessage.value = 'Unable to activate user.'
  } finally {
    isActionLoading.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchRoles()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Users Management</h2>
        <p class="text-gray-500 dark:text-gray-400">Create and manage system users.</p>
      </div>
      <Button class="bg-blue-600 hover:bg-blue-700 text-white" @click="isCreating = !isCreating">
        <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Create User' }}
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- CREATE USER FORM -->
    <Card v-if="isCreating">
      <CardHeader>
        <CardTitle>Create New User</CardTitle>
        <CardDescription>Add a new user to the system.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2 md:col-span-2">
          <Label>Full Name</Label>
          <Input v-model="userForm.name" placeholder="John Doe" />
        </div>
        <div class="space-y-2 md:col-span-2">
          <Label>Email Address</Label>
          <Input v-model="userForm.email" type="email" placeholder="john@example.com" />
        </div>
        <div class="space-y-2">
          <Label>Password</Label>
          <Input v-model="userForm.password" type="password" placeholder="••••••••" />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="userForm.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button :disabled="isCreating" @click="createUser">Create User</Button>
        </div>
      </CardContent>
    </Card>

    <!-- USERS TABLE -->
    <Card>
      <CardHeader>
        <CardTitle>All Users</CardTitle>
        <CardDescription>View and manage all system users.</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <div v-if="isLoading" class="text-center py-8">
          <p class="text-gray-500">Loading users...</p>
        </div>
        <div v-else-if="users.length === 0" class="text-center py-8">
          <p class="text-gray-500">No users found.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b">
                <th class="px-4 py-2 text-left text-sm font-medium">Name</th>
                <th class="px-4 py-2 text-left text-sm font-medium">Email</th>
                <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
                <th class="px-4 py-2 text-left text-sm font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-900/20">
                <td class="px-4 py-2 text-sm">{{ user.name }}</td>
                <td class="px-4 py-2 text-sm">{{ user.email }}</td>
                <td class="px-4 py-2 text-sm">
                  <Badge :variant="getStatusVariant(user.status)">{{ user.status }}</Badge>
                </td>
                <td class="px-4 py-2 text-sm space-x-2">
                  <Button v-if="user.status === 'active'" size="sm" variant="outline" :disabled="isActionLoading" @click="suspendUser(user.id)">
                    Suspend
                  </Button>
                  <Button v-else size="sm" variant="outline" :disabled="isActionLoading" @click="activateUser(user.id)">
                    Activate
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
