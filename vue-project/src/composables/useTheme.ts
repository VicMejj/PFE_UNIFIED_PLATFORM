import { computed, ref } from 'vue'

export type AppTheme = 'light' | 'dark'

const currentTheme = ref<AppTheme>('light')
const initialized = ref(false)

function applyTheme(theme: AppTheme, persist = true) {
  currentTheme.value = theme

  if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('dark', theme === 'dark')
  }

  if (persist && typeof window !== 'undefined') {
    localStorage.setItem('theme', theme)
  }
}

export function initializeTheme(preferredTheme?: AppTheme | null) {
  if (initialized.value && !preferredTheme) return

  const storedTheme = typeof window !== 'undefined'
    ? (localStorage.getItem('theme') as AppTheme | null)
    : null

  const resolvedTheme = preferredTheme ?? storedTheme ?? 'light'
  applyTheme(resolvedTheme, true)
  initialized.value = true
}

export function useTheme() {
  const theme = computed(() => currentTheme.value)

  function setTheme(nextTheme: AppTheme) {
    applyTheme(nextTheme)
  }

  function toggleTheme() {
    setTheme(currentTheme.value === 'light' ? 'dark' : 'light')
    return currentTheme.value
  }

  return {
    theme,
    setTheme,
    toggleTheme
  }
}
