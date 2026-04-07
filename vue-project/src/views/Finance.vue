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
import { financeApi } from '@/api/laravel/finance'
import { unwrapItems } from '@/api/http'

const accounts = ref<any[]>([])
const expenses = ref<any[]>([])
const isLoading = ref(true)
const isCreatingAccount = ref(false)
const isCreatingExpense = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const accountForm = reactive({
  name: '',
  account_type: 'cash',
  balance: ''
})

const expenseForm = reactive({
  description: '',
  amount: '',
  expense_type_id: '',
  date: new Date().toISOString().split('T')[0]
})

const accountColumns = [
  { key: 'name', label: 'Account Name' },
  { key: 'account_type', label: 'Type' },
  { key: 'balance', label: 'Balance' }
]

const expenseColumns = [
  { key: 'description', label: 'Description' },
  { key: 'amount', label: 'Amount' },
  { key: 'date', label: 'Date' }
]

const fetchAccounts = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await financeApi.getAccounts()
    accounts.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch accounts', err)
    errorMessage.value = 'Unable to load accounts.'
  } finally {
    isLoading.value = false
  }
}

const fetchExpenses = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await financeApi.getExpenses()
    expenses.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch expenses', err)
    errorMessage.value = 'Unable to load expenses.'
  } finally {
    isLoading.value = false
  }
}

const createAccount = async () => {
  isCreatingAccount.value = true
  feedback.value = ''
  errorMessage.value = ''

  try {
    await financeApi.createAccount({
      name: accountForm.name,
      account_type: accountForm.account_type,
      balance: Number(accountForm.balance)
    })

    feedback.value = 'Account created successfully.'
    accountForm.name = ''
    accountForm.balance = ''
    await fetchAccounts()
  } catch (err) {
    console.error('Failed to create account', err)
    errorMessage.value = 'Unable to create account.'
  } finally {
    isCreatingAccount.value = false
  }
}

const createExpense = async () => {
  isCreatingExpense.value = true
  feedback.value = ''
  errorMessage.value = ''

  try {
    await financeApi.createExpense({
      description: expenseForm.description,
      amount: Number(expenseForm.amount),
      expense_type_id: expenseForm.expense_type_id,
      date: expenseForm.date
    })

    feedback.value = 'Expense recorded successfully.'
    expenseForm.description = ''
    expenseForm.amount = ''
    expenseForm.date = new Date().toISOString().split('T')[0]
    await fetchExpenses()
  } catch (err) {
    console.error('Failed to create expense', err)
    errorMessage.value = 'Unable to record expense.'
  } finally {
    isCreatingExpense.value = false
  }
}

onMounted(() => {
  fetchAccounts()
  fetchExpenses()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Finance Dashboard</h2>
        <p class="text-gray-500 dark:text-gray-400">Manage accounts, expenses, and financial records.</p>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- ACCOUNTS SECTION -->
      <div class="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Create Account</CardTitle>
            <CardDescription>Add a new financial account.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label>Account Name</Label>
              <Input v-model="accountForm.name" placeholder="e.g., Main Bank Account" />
            </div>
            <div class="space-y-2">
              <Label>Type</Label>
              <select v-model="accountForm.account_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="credit-card">Credit Card</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label>Initial Balance</Label>
              <Input v-model="accountForm.balance" type="number" placeholder="0.00" />
            </div>
            <Button :disabled="isCreatingAccount" @click="createAccount">Create Account</Button>
          </CardContent>
        </Card>
      </div>

      <!-- EXPENSES SECTION -->
      <div class="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle>Record Expense</CardTitle>
            <CardDescription>Log a new company expense.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label>Description</Label>
              <Input v-model="expenseForm.description" placeholder="Expense description" />
            </div>
            <div class="space-y-2">
              <Label>Amount</Label>
              <Input v-model="expenseForm.amount" type="number" placeholder="0.00" />
            </div>
            <div class="space-y-2">
              <Label>Date</Label>
              <Input v-model="expenseForm.date" type="date" />
            </div>
            <Button :disabled="isCreatingExpense" @click="createExpense">Record Expense</Button>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- ACCOUNTS TABLE -->
    <Card>
      <CardHeader>
        <CardTitle>Accounts</CardTitle>
        <CardDescription>Manage your financial accounts.</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <DataTable 
          :columns="accountColumns" 
          :data="accounts" 
          :loading="isLoading"
          searchPlaceholder="Search accounts..."
          emptyMessage="No accounts found."
        />
      </CardContent>
    </Card>

    <!-- EXPENSES TABLE -->
    <Card>
      <CardHeader>
        <CardTitle>Expenses</CardTitle>
        <CardDescription>View recorded expenses.</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <DataTable 
          :columns="expenseColumns" 
          :data="expenses" 
          :loading="isLoading"
          searchPlaceholder="Search expenses..."
          emptyMessage="No expenses found."
        />
      </CardContent>
    </Card>
  </div>
</template>
