<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { X } from 'lucide-vue-next'
import { cn } from './utils'

interface Props {
  open: boolean
  title?: string
  description?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
  showClose?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  showClose: true
})

const emit = defineEmits(['close'])

const sizes = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
  full: 'max-w-[95vw]'
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.open) {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleEscape)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-bounce">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center sm:p-6" @click.self="emit('close')">
        <!-- Backdrop Blur Layer -->
        <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-all duration-500 animate-in fade-in" @click="emit('close')"></div>
        
        <div 
          :class="cn(
            'relative z-10 mt-8 flex max-h-[90vh] w-full flex-col overflow-hidden rounded-[2rem] glass-card premium-shadow transition-all duration-500 sm:mt-0',
            sizes[size]
          )"
          @click.stop
        >
          <!-- Premium Header Decoration -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-blue-500/40 to-transparent"></div>

          <!-- Header -->
          <div v-if="title || showClose" class="flex items-center justify-between px-8 pt-8 pb-4">
            <div class="flex-1">
              <h3 v-if="title" class="text-2xl font-black tracking-tight text-slate-900 dark:text-white leading-tight">{{ title }}</h3>
              <p v-if="description" class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mt-1.5">{{ description }}</p>
            </div>
            <button 
              v-if="showClose" 
              @click="emit('close')" 
              class="group p-2.5 rounded-2xl bg-slate-100/50 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-400 dark:text-slate-500 hover:text-rose-500 transition-all active:scale-90"
            >
              <X class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" />
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 min-h-0 overflow-y-auto px-8 py-4 no-scrollbar">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="mt-auto flex shrink-0 flex-col justify-end gap-3 border-t border-white/10 bg-slate-50/30 px-8 py-6 backdrop-blur-md dark:border-white/5 dark:bg-slate-900/40 sm:flex-row">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-bounce-enter-active {
  animation: modal-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.modal-bounce-leave-active {
  animation: modal-out 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes modal-in {
  from { opacity: 0; transform: scale(0.92) translateY(20px); filter: blur(4px); }
  to { opacity: 1; transform: scale(1) translateY(0); filter: blur(0); }
}

@keyframes modal-out {
  from { opacity: 1; transform: scale(1); filter: blur(0); }
  to { opacity: 0; transform: scale(0.95) translateY(10px); filter: blur(2px); }
}

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
