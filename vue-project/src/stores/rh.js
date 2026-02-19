import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as rhApi from '@/api/django/rh'

export const useRhStore = defineStore('rh', () => {
  const employees = ref([])
  const contracts = ref([])
  const loading = ref(false)
  const error = ref(null)

  const employeeCount = computed(() => employees.value.length)
  const contractCount = computed(() => contracts.value.length)

  async function fetchEmployees() {
    loading.value = true
    try {
      const data = await rhApi.getEmployees()
      employees.value = Array.isArray(data) ? data : data.results || []
      error.value = null
    } catch (err) {
      error.value = err.message
      console.error('Error fetching employees:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchEmployee(id) {
    try {
      return await rhApi.getEmployee(id)
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function createEmployee(employeeData) {
    try {
      const newEmployee = await rhApi.createEmployee(employeeData)
      employees.value.push(newEmployee)
      return newEmployee
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function updateEmployee(id, employeeData) {
    try {
      const updated = await rhApi.updateEmployee(id, employeeData)
      const index = employees.value.findIndex(e => e.id === id)
      if (index !== -1) {
        employees.value[index] = updated
      }
      return updated
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function deleteEmployee(id) {
    try {
      await rhApi.deleteEmployee(id)
      employees.value = employees.value.filter(e => e.id !== id)
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function fetchContracts() {
    loading.value = true
    try {
      const data = await rhApi.getContracts()
      contracts.value = Array.isArray(data) ? data : data.results || []
      error.value = null
    } catch (err) {
      error.value = err.message
      console.error('Error fetching contracts:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchContract(id) {
    try {
      return await rhApi.getContract(id)
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function createContract(contractData) {
    try {
      const newContract = await rhApi.createContract(contractData)
      contracts.value.push(newContract)
      return newContract
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function updateContract(id, contractData) {
    try {
      const updated = await rhApi.updateContract(id, contractData)
      const index = contracts.value.findIndex(c => c.id === id)
      if (index !== -1) {
        contracts.value[index] = updated
      }
      return updated
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  return {
    employees,
    contracts,
    loading,
    error,
    employeeCount,
    contractCount,
    fetchEmployees,
    fetchEmployee,
    createEmployee,
    updateEmployee,
    deleteEmployee,
    fetchContracts,
    fetchContract,
    createContract,
    updateContract
  }
})
