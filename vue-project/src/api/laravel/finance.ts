import { laravelApi } from '@/api/http'
import { unwrapResponse } from '@/api/http'

export const financeApi = {
  // ===== ACCOUNTS =====
  async getAccounts() {
    const response = await laravelApi.get('/finance/accounts')
    return unwrapResponse(response)
  },

  async createAccount(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/accounts', payload)
    return unwrapResponse(response)
  },

  async getAccount(id: number) {
    const response = await laravelApi.get(`/finance/accounts/${id}`)
    return unwrapResponse(response)
  },

  async updateAccount(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/accounts/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteAccount(id: number) {
    const response = await laravelApi.delete(`/finance/accounts/${id}`)
    return unwrapResponse(response)
  },

  // ===== DEPOSITS =====
  async getDeposits() {
    const response = await laravelApi.get('/finance/deposits')
    return unwrapResponse(response)
  },

  async createDeposit(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/deposits', payload)
    return unwrapResponse(response)
  },

  async getDeposit(id: number) {
    const response = await laravelApi.get(`/finance/deposits/${id}`)
    return unwrapResponse(response)
  },

  async updateDeposit(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/deposits/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteDeposit(id: number) {
    const response = await laravelApi.delete(`/finance/deposits/${id}`)
    return unwrapResponse(response)
  },

  // ===== EXPENSES =====
  async getExpenses() {
    const response = await laravelApi.get('/finance/expenses')
    return unwrapResponse(response)
  },

  async createExpense(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/expenses', payload)
    return unwrapResponse(response)
  },

  async getExpense(id: number) {
    const response = await laravelApi.get(`/finance/expenses/${id}`)
    return unwrapResponse(response)
  },

  async updateExpense(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/expenses/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteExpense(id: number) {
    const response = await laravelApi.delete(`/finance/expenses/${id}`)
    return unwrapResponse(response)
  },

  // ===== EXPENSE TYPES =====
  async getExpenseTypes() {
    const response = await laravelApi.get('/finance/expense-types')
    return unwrapResponse(response)
  },

  async createExpenseType(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/expense-types', payload)
    return unwrapResponse(response)
  },

  async getExpenseType(id: number) {
    const response = await laravelApi.get(`/finance/expense-types/${id}`)
    return unwrapResponse(response)
  },

  async updateExpenseType(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/expense-types/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteExpenseType(id: number) {
    const response = await laravelApi.delete(`/finance/expense-types/${id}`)
    return unwrapResponse(response)
  },

  // ===== PAYEES =====
  async getPayees() {
    const response = await laravelApi.get('/finance/payees')
    return unwrapResponse(response)
  },

  async createPayee(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/payees', payload)
    return unwrapResponse(response)
  },

  async getPayee(id: number) {
    const response = await laravelApi.get(`/finance/payees/${id}`)
    return unwrapResponse(response)
  },

  async updatePayee(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/payees/${id}`, payload)
    return unwrapResponse(response)
  },

  async deletePayee(id: number) {
    const response = await laravelApi.delete(`/finance/payees/${id}`)
    return unwrapResponse(response)
  },

  // ===== PAYERS =====
  async getPayers() {
    const response = await laravelApi.get('/finance/payers')
    return unwrapResponse(response)
  },

  async createPayer(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/payers', payload)
    return unwrapResponse(response)
  },

  async getPayer(id: number) {
    const response = await laravelApi.get(`/finance/payers/${id}`)
    return unwrapResponse(response)
  },

  async updatePayer(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/payers/${id}`, payload)
    return unwrapResponse(response)
  },

  async deletePayer(id: number) {
    const response = await laravelApi.delete(`/finance/payers/${id}`)
    return unwrapResponse(response)
  },

  // ===== INCOME TYPES =====
  async getIncomeTypes() {
    const response = await laravelApi.get('/finance/income-types')
    return unwrapResponse(response)
  },

  async createIncomeType(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/income-types', payload)
    return unwrapResponse(response)
  },

  async getIncomeType(id: number) {
    const response = await laravelApi.get(`/finance/income-types/${id}`)
    return unwrapResponse(response)
  },

  async updateIncomeType(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/income-types/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteIncomeType(id: number) {
    const response = await laravelApi.delete(`/finance/income-types/${id}`)
    return unwrapResponse(response)
  },

  // ===== TRANSFER BALANCES =====
  async getTransferBalances() {
    const response = await laravelApi.get('/finance/transfer-balances')
    return unwrapResponse(response)
  },

  async createTransferBalance(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/finance/transfer-balances', payload)
    return unwrapResponse(response)
  },

  async getTransferBalance(id: number) {
    const response = await laravelApi.get(`/finance/transfer-balances/${id}`)
    return unwrapResponse(response)
  },

  async updateTransferBalance(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/finance/transfer-balances/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTransferBalance(id: number) {
    const response = await laravelApi.delete(`/finance/transfer-balances/${id}`)
    return unwrapResponse(response)
  }
}
