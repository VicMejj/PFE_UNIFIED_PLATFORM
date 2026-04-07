<script setup lang="ts">
import { ArrowRight, Building2, Users, ShieldCheck, Zap } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import BrandMark from '@/components/common/BrandMark.vue'
</script>

<template>
  <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(125,211,252,0.24),_transparent_40%),linear-gradient(180deg,_#f8fbff_0%,_#eff6ff_50%,_#ffffff_100%)] text-slate-900 selection:bg-sky-200 dark:bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.25),_transparent_35%),linear-gradient(180deg,_#020617_0%,_#0f172a_55%,_#111827_100%)] dark:text-white">
    <!-- Animated Background Gradients -->
    <div class="absolute inset-0 z-0">
      <div class="absolute top-[-10%] left-[-10%] h-[40%] w-[40%] rounded-full bg-sky-300/40 blur-[120px] animate-pulse-slow dark:bg-blue-600/30"></div>
      <div class="absolute bottom-[-10%] right-[-10%] h-[40%] w-[40%] rounded-full bg-amber-200/40 blur-[120px] animate-pulse-slow dark:bg-indigo-600/30" style="animation-delay: 2s"></div>
    </div>

    <!-- Navigation -->
    <nav class="relative z-10 flex items-center justify-between border-b border-slate-200/70 px-6 py-4 backdrop-blur-sm dark:border-white/10 lg:px-12">
      <BrandMark size="sm" title="Unified Platform" subtitle="Operations OS" />
      <div class="space-x-4 flex items-center">
        <ThemeToggle subtle />
        <RouterLink to="/login" class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-950 dark:text-gray-300 dark:hover:text-white">
          Sign In
        </RouterLink>
        <RouterLink to="/register">
          <Button class="border border-slate-200 bg-white text-slate-900 hover:bg-slate-50 dark:border-white/20 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
            Get Started
          </Button>
        </RouterLink>
      </div>
    </nav>

    <!-- Hero Section -->
    <main class="relative z-10 flex flex-col items-center justify-center min-h-[calc(100vh-80px)] px-4 text-center">
      <div class="animate-in slide-in-from-bottom-8 fade-in duration-1000 ease-out space-y-8 max-w-4xl">
        <div class="mb-4 inline-flex items-center space-x-2 rounded-full border border-sky-200 bg-white/80 px-3 py-1 text-sm font-medium text-sky-700 backdrop-blur-md dark:border-white/10 dark:bg-white/5 dark:text-blue-300">
          <Zap class="w-4 h-4" />
          <span>Next-Generation Workforce Operations</span>
        </div>
        
        <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight">
          Manage your company <br/>
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-500 to-indigo-500 dark:from-blue-400 dark:to-indigo-400">
            intelligently.
          </span>
        </h1>
        
        <p class="max-w-2xl mx-auto text-lg leading-relaxed text-slate-600 dark:text-gray-400 md:text-xl">
          Run payroll, employee operations, benefits, approvals, and AI-assisted workflows from one polished control center.
        </p>
        
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
          <RouterLink to="/login">
            <Button size="lg" class="group h-14 bg-blue-600 px-8 text-lg text-white shadow-xl shadow-blue-900/20 transition-all hover:scale-105 hover:bg-blue-700">
              Enter your company now
              <ArrowRight class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </Button>
          </RouterLink>
          <RouterLink to="/register">
            <Button size="lg" class="h-14 border border-slate-200 bg-white px-8 text-lg text-slate-900 backdrop-blur-sm transition-all hover:bg-slate-50 dark:border-white/20 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
              Create an account
            </Button>
          </RouterLink>
        </div>
      </div>

      <!-- Feature Highlights -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-24 max-w-5xl mx-auto px-4 animate-in slide-in-from-bottom-12 fade-in duration-1000 delay-500 fill-mode-both">
        <div class="flex flex-col items-center rounded-2xl border border-slate-200/80 bg-white/80 p-6 backdrop-blur-sm transition-colors hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
          <Users class="w-10 h-10 text-blue-400 mb-4" />
          <h3 class="text-lg font-semibold mb-2">Unified Workforce</h3>
          <p class="text-sm text-slate-500 dark:text-gray-400">Manage all employees, departments, and roles in one centralized hub.</p>
        </div>
        <div class="flex flex-col items-center rounded-2xl border border-slate-200/80 bg-white/80 p-6 backdrop-blur-sm transition-colors hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
          <Building2 class="w-10 h-10 text-indigo-400 mb-4" />
          <h3 class="text-lg font-semibold mb-2">Smart Organization</h3>
          <p class="text-sm text-slate-500 dark:text-gray-400">Automate payroll, leave requests, and track attendance seamlessly.</p>
        </div>
        <div class="flex flex-col items-center rounded-2xl border border-slate-200/80 bg-white/80 p-6 backdrop-blur-sm transition-colors hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
          <ShieldCheck class="w-10 h-10 text-emerald-400 mb-4" />
          <h3 class="text-lg font-semibold mb-2">AI-Driven Insights</h3>
          <p class="text-sm text-slate-500 dark:text-gray-400">Predict turnover and get smart recommendations for your business.</p>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
@keyframes pulse-slow {
  0%, 100% { opacity: 0.5; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.1); }
}
.animate-pulse-slow {
  animation: pulse-slow 8s ease-in-out infinite;
}
</style>
