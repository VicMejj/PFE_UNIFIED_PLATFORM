<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  LayoutDashboard,
  Users,
  Calendar,
  FileText,
  Briefcase,
  Heart,
  Bell,
  Settings,
  UserCircle2,
  LogOut,
  X,
  ChevronRight,
  Sparkles,
  ScanEye,
  Building2,
  BarChart3,
  ShieldCheck
} from 'lucide-vue-next'
import { getAvatarUrl } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import BrandMark from '@/components/common/BrandMark.vue'

const props = defineProps<{ isOpen: boolean }>()
const emit = defineEmits(['close'])

const router = useRouter()
const route = useRoute()

const auth = useAuthStore()
const user = computed(() => auth.user)
const role = computed(() => user.value?.role ?? '')

const visibleLinks = computed(() => {
  const commonLinks = [
    { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Profile', href: '/profile', icon: UserCircle2 },
      { name: 'Leave Requests', href: '/leave-requests', icon: Calendar },
      { name: 'Benefits', href: '/social/benefits', icon: Heart },
      { name: 'Insurance Hub', href: '/insurance', icon: FileText },
      { name: 'Notifications', href: '/notifications', icon: Bell },
      { name: 'Settings', href: '/settings', icon: Settings }
  ]

  if (role.value === 'admin') {
    return [
      { name: 'Admin Command', href: '/admin', icon: LayoutDashboard },
      { name: 'Organization', href: '/admin/organization', icon: Building2 },
      { name: 'Talent Pool', href: '/rh/employees', icon: Users },
      { name: 'Leave Queue', href: '/rh/leaves', icon: Calendar },
      { name: 'Payroll Engine', href: '/rh/payroll', icon: Briefcase },
      { name: 'Employee Scores', href: '/social/scores', icon: BarChart3 },
      { name: 'Insurance Hub', href: '/insurance', icon: FileText },
      { name: 'Insurance Claims', href: '/assurance/claims', icon: ShieldCheck },
      { name: 'AI Center', href: '/ai-analytics', icon: Sparkles },
      { name: 'Fraud Lab', href: '/documents', icon: ScanEye },
      { name: 'Agreements', href: '/rh/contracts', icon: FileText },
      { name: 'Social Hub', href: '/social/benefits', icon: Heart },
      { name: 'System Alerts', href: '/notifications', icon: Bell },
      { name: 'Settings', href: '/settings', icon: Settings }
    ]
  }

  if (role.value === 'rh_manager') {
    return [
      { name: 'RH Oversight', href: '/rh', icon: LayoutDashboard },
      { name: 'Organization', href: '/rh/organization', icon: Building2 },
      { name: 'Employees', href: '/rh/employees', icon: Users },
      { name: 'Leave Requests', href: '/rh/leaves', icon: Calendar },
      { name: 'Payroll Docs', href: '/rh/payroll', icon: Briefcase },
      { name: 'Employee Scores', href: '/social/scores', icon: BarChart3 },
      { name: 'Insurance Hub', href: '/insurance', icon: FileText },
      { name: 'Insurance Claims', href: '/assurance/claims', icon: ShieldCheck },
      { name: 'AI Center', href: '/ai-analytics', icon: Sparkles },
      { name: 'Fraud Lab', href: '/documents', icon: ScanEye },
      { name: 'Compliance', href: '/rh/contracts', icon: FileText },
      { name: 'Benefits', href: '/social/benefits', icon: Heart },
      { name: 'Notifications', href: '/notifications', icon: Bell },
      { name: 'Settings', href: '/settings', icon: Settings }
    ]
  }

  if (role.value === 'manager') {
    return [
      { name: 'Team Dashboard', href: '/manager', icon: LayoutDashboard },
      { name: 'Employee Scores', href: '/social/scores', icon: BarChart3 },
      { name: 'Insurance Claims', href: '/assurance/claims', icon: ShieldCheck },
      { name: 'AI Center', href: '/ai-analytics', icon: Sparkles },
      ...commonLinks.filter((item) => item.name !== 'Dashboard')
    ]
  }

  if (role.value === 'employee') {
    return [
      { name: 'My Activity', href: '/employee', icon: LayoutDashboard },
      ...commonLinks.filter((item) => item.name !== 'Dashboard' && item.name !== 'Insurance Hub')
    ]
  }

  return commonLinks
})

const avatarUrl = computed(() => getAvatarUrl(user.value))

const handleLogout = async () => {
  await auth.logout()
  router.push('/login')
}

const getInitials = (name: string) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}
</script>

<template>
  <aside
    :class="[
      'fixed top-0 left-0 z-40 h-screen transition-all duration-500 ease-in-out w-[280px] border-r border-white/5 bg-white/40 backdrop-blur-3xl dark:border-white/5 dark:bg-slate-950/60 premium-shadow',
      isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <div class="h-full flex flex-col">
      <!-- Logo Area -->
      <div class="flex items-center justify-between px-8 py-8">
        <BrandMark size="sm" title="Unified Platform" subtitle="Workforce hub" class="scale-110 origin-left" />
        <button
          @click="emit('close')"
          class="lg:hidden p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-blue-500 transition-all"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Domain Label -->
      <div class="px-8 mb-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-[9px] font-black uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400">
           <span class="w-1 h-1 rounded-full bg-blue-500 animate-pulse"></span>
           {{ role ? role.replace('_', ' ') : 'operational hub' }}
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-6 no-scrollbar">
        <div class="space-y-1">
          <RouterLink
            v-for="item in visibleLinks"
            :key="item.name"
            :to="item.href"
            :class="[
              'group relative flex items-center rounded-2xl px-4 py-3 text-sm font-bold transition-all duration-300',
              route.path === item.href || (item.href !== '/dashboard' && route.path.startsWith(item.href))
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 translate-x-1'
                : 'text-slate-500 hover:text-slate-900 hover:bg-white dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-900/40'
            ]"
          >
            <component :is="item.icon" :class="['mr-3 h-5 w-5 transition-transform duration-300', route.path === item.href ? 'scale-110' : 'group-hover:scale-110']" />
            <span class="flex-1">{{ item.name }}</span>
            <ChevronRight v-if="route.path === item.href" class="h-4 w-4 opacity-50" />
            
            <!-- Passive indicator -->
            <div v-if="route.path === item.href" class="absolute -right-1 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-blue-500 rounded-full blur-[2px]"></div>
          </RouterLink>
        </div>
      </nav>

      <!-- Bottom Profile Island -->
      <div class="p-6">
        <div class="relative group p-4 rounded-3xl bg-white/60 dark:bg-slate-900/60 border border-white/40 dark:border-white/5 shadow-xl transition-all hover:bg-white dark:hover:bg-slate-900">
          <div class="flex items-center space-x-3">
            <div class="relative flex h-11 w-11 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-blue-600/10 text-sm font-black text-blue-600 ring-2 ring-white/20 dark:ring-white/5">
              <img v-if="avatarUrl" :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
              <span v-else>{{ user ? getInitials(user.name) : 'U' }}</span>
              <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
            </div>
            
            <div class="flex-1 min-w-0">
              <p class="truncate text-sm font-black text-slate-900 dark:text-white leading-tight">
                {{ user?.name || 'Authorized User' }}
              </p>
              <p class="truncate text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mt-0.5">
                {{ role ? role.replace('_', ' ') : 'Access Point' }}
              </p>
            </div>

            <button
              type="button"
              class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-all duration-300"
              @click="handleLogout"
              title="Log Out"
            >
              <LogOut class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.premium-shadow {
  box-shadow: 20px 0 80px -20px rgba(0, 0, 0, 0.05);
}
.dark .premium-shadow {
  box-shadow: 20px 0 80px -20px rgba(0, 0, 0, 0.4);
}
</style>
