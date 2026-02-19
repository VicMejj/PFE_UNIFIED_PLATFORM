import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/views/login.vue'
import Register from '@/views/register.vue'
import AdminDashboard from '@/views/Admin/AdminDashboard.vue'
import RHDashboard from '@/views/Rh/RHdashboard.vue'
import Employee from '@/views/Employee/MyData.vue'
const routes = [
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/admin', component: AdminDashboard },
  { path: '/rh', component: RHDashboard },
  { path: '/employee', component: Employee }
]

export default createRouter({
  history: createWebHistory(),
  routes
})