import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Auth views
import Login from '@/views/login.vue'
import Register from '@/views/register.vue'
import ForgotPassword from '@/views/ForgotPassword.vue'
import ResetPassword from '@/views/ResetPassword.vue'

// Layout
import AppLayout from '@/layouts/AppLayout.vue'

// Views (lazy-loaded for perf)
const Dashboard = () => import('@/views/Dashboard.vue')
const AdminDashboard = () => import('@/views/Admin/AdminDashboard.vue')
const RHDashboard = () => import('@/views/Rh/RHdashboard.vue')
const Contracts = () => import('@/views/Rh/Contracts.vue')
const ContractReview = () => import('@/views/ContractReview.vue')
const Payroll = () => import('@/views/Rh/Payroll.vue')
const Profile = () => import('@/views/Profile.vue')
const Settings = () => import('@/views/Settings.vue')
const Notifications = () => import('@/views/Notifications.vue')
const LeaveRequests = () => import('@/views/LeaveRequests.vue')
const MyData = () => import('@/views/Employee/MyData.vue')
const AssurancePolicies = () => import('@/views/Assurance/Policies.vue')
const AssuranceClaims = () => import('@/views/Assurance/Claims.vue')
const AssuranceEmployeeClaims = () => import('@/views/Assurance/EmployeeClaims.vue')
const AssurancePlans = () => import('@/views/Assurance/Plans.vue')
const SocialBenefits = () => import('@/views/Social/Benefits.vue')
const EmployeeBenefits = () => import('@/views/Social/EmployeeBenefits.vue')
const SocialClaims = () => import('@/views/Social/Claims.vue')
const EmployeeScoreDashboard = () => import('@/views/EmployeeScoreDashboard.vue')
const Finance = () => import('@/views/Finance.vue')
const Attendance = () => import('@/views/Attendance.vue')
const Documents = () => import('@/views/Documents.vue')
const InsuranceHub = () => import('@/views/InsuranceHub.vue')
const Chatbot = () => import('@/views/Chatbot.vue')
const AIAnalytics = () => import('@/views/AIAnalytics.vue')
const AdminUsers = () => import('@/views/Admin/Users.vue')
const AdminRoles = () => import('@/views/Admin/Roles.vue')
const AdminOrganization = () => import('@/views/Admin/Organization.vue')

function resolveDefaultRoute(role?: string | null) {
  if (role === 'admin') return '/admin'
  if (role === 'rh_manager') return '/rh'
  if (role === 'manager') return '/manager'
  if (role === 'employee') return '/employee'
  return '/dashboard'
}

function redirectIfNeeded(next: (location?: string | false | void) => void, toPath: string, targetPath: string) {
  if (toPath === targetPath) return next()
  return next(targetPath)
}

const routes = [
  // Public auth routes
  { path: '/', component: () => import('@/views/Landing.vue'), meta: { public: true } },
  { path: '/login', component: Login, meta: { public: true } },
  { path: '/register', component: Register, meta: { public: true } },
  { path: '/verify-email', component: () => import('@/views/VerifyEmail.vue'), meta: { public: true } },
  { path: '/forgot-password', component: ForgotPassword, meta: { public: true } },
  { path: '/reset-password', component: ResetPassword, meta: { public: true } },

  // Protected — inside AppLayout
  {
    path: '/',
    component: AppLayout,
    children: [
      { path: 'dashboard', name: 'dashboard', component: Dashboard },
      { path: 'profile', name: 'profile', component: Profile },
      { path: 'settings', name: 'settings', component: Settings },
      { path: 'notifications', name: 'notifications', component: Notifications },
      { path: 'leave-requests', name: 'leave-requests', component: LeaveRequests },
      { path: 'contract-review', name: 'contract-review', component: ContractReview },

      // Admin
      { path: 'admin', name: 'admin', component: AdminDashboard, meta: { role: 'admin' } },
      { path: 'admin/users', name: 'admin-users', component: AdminUsers, meta: { role: 'admin' } },
      { path: 'admin/roles', name: 'admin-roles', component: AdminRoles, meta: { role: 'admin' } },
      { path: 'admin/organization', name: 'admin-organization', component: AdminOrganization, meta: { role: 'admin' } },

      // RH Manager
      { path: 'rh', name: 'rh', component: RHDashboard, meta: { role: 'rh_manager' } },
      { path: 'rh/employees', name: 'employees', component: () => import('@/views/Rh/Employees.vue'), meta: { role: 'rh_manager' } },
      { path: 'rh/leaves', name: 'leaves', component: LeaveRequests, meta: { role: 'rh_manager' } },
      { path: 'rh/contracts', name: 'contracts', component: Contracts, meta: { role: 'rh_manager' } },
      { path: 'rh/payroll', name: 'payroll', component: Payroll, meta: { role: 'rh_manager' } },
      { path: 'rh/organization', name: 'rh-organization', component: AdminOrganization, meta: { role: 'rh_manager' } },
      { path: 'rh/notifications', redirect: '/notifications', meta: { role: 'rh_manager' } },
      
      // Manager
      { path: 'manager', name: 'manager', component: () => import('@/views/Manager/ManagerDashboard.vue'), meta: { role: 'manager' } },

      // Employee
      { path: 'employee', name: 'employee', component: MyData, meta: { role: 'employee' } },
      { path: 'employee/my-data', redirect: '/employee' },

      // Shared features (Finance, Attendance, Documents)
      { path: 'finance', name: 'finance', component: Finance },
      { path: 'attendance', name: 'attendance', component: Attendance },
      { path: 'documents', name: 'documents', component: Documents },
      { path: 'insurance', name: 'insurance-hub', component: InsuranceHub },

      // AI Features (restricted to admin/manager/rh)
      { path: 'chatbot', name: 'chatbot', component: Chatbot, meta: { restrictedRoles: ['admin', 'manager', 'rh_manager'] } },
      { path: 'ai-analytics', name: 'ai-analytics', component: AIAnalytics, meta: { restrictedRoles: ['admin', 'manager', 'rh_manager'] } },

      // Insurance (admin/rh only)
      { path: 'assurance/policies', name: 'policies', component: AssurancePolicies, meta: { restrictedRoles: ['admin', 'rh_manager'] } },
      { path: 'assurance/plans', name: 'plans', component: AssurancePlans, meta: { restrictedRoles: ['admin', 'rh_manager'] } },
      { path: 'assurance/claims', name: 'claims', component: AssuranceClaims, meta: { restrictedRoles: ['admin', 'rh_manager'] } },
      
      // Insurance (employee)
      { path: 'assurance/my-claims', name: 'my-claims', component: AssuranceEmployeeClaims, meta: { role: 'employee' } },

      // Social
      { path: 'social/benefits', name: 'benefits', component: SocialBenefits },
      { path: 'social/employee-benefits', name: 'employee-benefits', component: EmployeeBenefits },
      { path: 'social/claims', name: 'social-claims', component: SocialClaims },
      { path: 'social/scores', name: 'employee-scores', component: EmployeeScoreDashboard, meta: { restrictedRoles: ['admin', 'manager', 'rh_manager'] } },
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

/**
 * Navigation guard with proper auth handling
 * Uses the auth store for role checking instead of raw localStorage
 */
router.beforeEach(async (to, _from, next) => {
  const auth = useAuthStore()
  
  // Initialize auth if not done yet
  if (!auth.isInitialized) {
    await auth.initializeAuth()
  }

  const token = auth.laravelToken
  const user = auth.user

  // Public routes - redirect to dashboard if already logged in
  if (to.meta.public) {
    if (token && user && to.path !== '/verify-email') {
      return redirectIfNeeded(next, to.path, resolveDefaultRoute(user.role))
    }
    return next()
  }

  // Not authenticated - redirect to login
  if (!token) {
    return next('/login')
  }

  if (to.path === '/dashboard') {
    return redirectIfNeeded(next, to.path, resolveDefaultRoute(user?.role))
  }

  // Role-specific route protection
  if (to.meta.role) {
    const requiredRole = to.meta.role as string
    
    // Admin can access everything
    if (user?.role === 'admin') {
      return next()
    }
    
    // Check if user has the required role
    if (user?.role && user.role !== requiredRole) {
      // Redirect to appropriate dashboard based on user's role
      return redirectIfNeeded(next, to.path, resolveDefaultRoute(user.role))
    }
  }

  // Restricted roles (for AI/smart features)
  if (to.meta.restrictedRoles) {
    const restrictedRoles = to.meta.restrictedRoles as string[]
    if (user?.role && !restrictedRoles.includes(user.role)) {
      return next(resolveDefaultRoute(user?.role))
    }
  }

  next()
})

export default router
