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
  X
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
    { name: 'Notifications', href: '/notifications', icon: Bell },
    { name: 'Settings', href: '/settings', icon: Settings }
  ]

  if (role.value === 'admin') {
    return [
      { name: 'Admin Command Center', href: '/admin', icon: LayoutDashboard },
      { name: 'Employees', href: '/rh/employees', icon: Users },
      { name: 'Leave Requests', href: '/rh/leaves', icon: Calendar },
      { name: 'Payroll', href: '/rh/payroll', icon: Briefcase },
      { name: 'Contracts', href: '/rh/contracts', icon: FileText },
      { name: 'Benefits', href: '/social/benefits', icon: Heart },
      { name: 'Notifications', href: '/notifications', icon: Bell },
      { name: 'Profile', href: '/profile', icon: UserCircle2 },
      { name: 'Settings', href: '/settings', icon: Settings }
    ]
  }

  if (role.value === 'rh_manager') {
    return [
      { name: 'RH Command Center', href: '/rh', icon: LayoutDashboard },
      { name: 'Employees', href: '/rh/employees', icon: Users },
      { name: 'Leave Requests', href: '/rh/leaves', icon: Calendar },
      { name: 'Payroll', href: '/rh/payroll', icon: Briefcase },
      { name: 'Contracts', href: '/rh/contracts', icon: FileText },
      { name: 'Benefits', href: '/social/benefits', icon: Heart },
      { name: 'Notifications', href: '/notifications', icon: Bell },
      { name: 'Profile', href: '/profile', icon: UserCircle2 },
      { name: 'Settings', href: '/settings', icon: Settings }
    ]
  }

  if (role.value === 'manager') {
    return [
      { name: 'Manager Dashboard', href: '/manager', icon: LayoutDashboard },
      ...commonLinks.filter((item) => item.name !== 'Dashboard')
    ]
  }

  if (role.value === 'employee') {
    return [
      { name: 'My Workspace', href: '/employee', icon: LayoutDashboard },
      ...commonLinks.filter((item) => item.name !== 'Dashboard')
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
      'fixed top-0 left-0 z-40 h-screen transition-transform w-[280px] border-r border-slate-200/40 bg-white/95 backdrop-blur-lg dark:border-slate-700/40 dark:bg-slate-950/95',
      isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <div class="h-full flex flex-col">
      <div class="flex items-center justify-between px-6 py-5 dark:border-slate-800">
        <BrandMark size="sm" title="Unified Platform" subtitle="Operations OS" />
        <button
          @click="emit('close')"
          class="lg:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
        >
          <X class="h-6 w-6" />
        </button>
      </div>

      <div class="px-6 py-3 text-xs uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500 font-medium">
        {{ role ? role.replace('_', ' ') : 'workspace' }}
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-3">
        <div class="space-y-1">
          <RouterLink
            v-for="item in visibleLinks"
            :key="item.name"
            :to="item.href"
            :class="[
              'group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200',
              route.path === item.href || (item.href !== '/dashboard' && route.path.startsWith(item.href))
                ? 'bg-blue-500/10 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'
                : 'text-slate-700 hover:bg-slate-100/80 dark:text-slate-300 dark:hover:bg-slate-900/50'
            ]"
          >
            <component :is="item.icon" class="mr-3 h-5 w-5" />
            <span>{{ item.name }}</span>
          </RouterLink>
        </div>
      </nav>

      <div class="p-4">
        <div class="rounded-xl bg-gradient-to-br from-slate-50 to-slate-100/50 px-4 py-4 dark:from-slate-900/50 dark:to-slate-800/30">
          <div class="flex items-center space-x-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-200/50 text-sm font-semibold text-slate-600 dark:bg-slate-800/50">
            <img v-if="avatarUrl" :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
            <span v-else>{{ user ? getInitials(user.name) : 'U' }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
              {{ user?.name || 'User' }}
            </p>
            <p class="truncate text-xs capitalize text-slate-500 dark:text-slate-400">
              {{ role ? role.replace('_', ' ') : 'Employee' }}
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-500 transition-all duration-200 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-white"
            @click="handleLogout"
          >
            <LogOut class="h-4 w-4" />
          </button>
        </div>
        </div>
      </div>
    </div>
  </aside>
</template>
