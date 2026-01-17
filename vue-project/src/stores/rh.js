import { defineStore } from 'pinia'
import { getEmployees } from '@/api/django/rh'

export const useRhStore = defineStore('rh', {
  state: () => ({
    employees: []
  }),

  actions: {
    async fetchEmployees() {
      this.employees = await getEmployees()
    }
  }
})
