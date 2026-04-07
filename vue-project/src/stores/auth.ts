import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import laravelAuthApi from '@/api/laravel/auth'
import { initializeTheme } from '@/composables/useTheme'

export interface UserRole {
  id: number
  name: string
  guard_name: string
}

export interface UserPermission {
  id: number
  name: string
  guard_name: string
}

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  avatar: string | null
  avatar_url?: string | null
  lang: string
  dark_mode?: boolean | null
  messenger_color?: string | null
  created_at: string
  updated_at: string
  roles: string[]
  permissions: string[]
  role: string // Primary role for routing (mapped from Laravel roles)
  allRoles: string[]
}

export interface LoginResponse {
  user: User
  token: string
  expires_in: number
  roles: string[]
  permissions: string[]
}

export interface RegisterResponse {
  user: User
  verification_required: boolean
  otp_expires_in_minutes: number
}

export interface VerifyOtpResponse {
  user: User
  roles: string[]
  permissions: string[]
  token: string
  expires_in: number
}

export interface MeResponse {
  user: User
  roles: string[]
  permissions: string[]
}

export interface ForgotPasswordResponse {
  otp_expires_in_minutes: number
}

export interface RefreshResponse {
  token: string
  expires_in: number
}

/**
 * Map Laravel roles to frontend route roles
 * Laravel: admin, manager, rh, user
 * Frontend expects: admin, manager, rh_manager, employee
 */
function mapLaravelRoleToRouteRole(laravelRoles: string[]): string {
  if (!Array.isArray(laravelRoles)) return 'employee'
  
  // Priority order: admin > rh > manager > user
  if (laravelRoles.includes('admin')) return 'admin'
  if (laravelRoles.includes('rh')) return 'rh_manager'
  if (laravelRoles.includes('manager')) return 'manager'
  return 'employee'
}

/**
 * Normalize user object from Laravel response
 * Adds computed role and ensures consistent structure
 */
function normalizeUser(apiResponse: any): User | null {
  const user = apiResponse.user || null
  const roles = apiResponse.roles || user?.roles?.map((r: UserRole) => r.name) || []
  const permissions = apiResponse.permissions || user?.permissions?.map((p: UserPermission) => p.name) || []
  
  if (!user) return null
  
  return {
    ...user,
    roles,
    permissions,
    role: mapLaravelRoleToRouteRole(roles), // Primary role for routing
    allRoles: roles // Keep all roles for permission checks
  }
}

function resolveThemePreference(darkMode?: boolean | null): 'light' | 'dark' | undefined {
  if (darkMode === true) return 'dark'
  if (darkMode === false) return 'light'
  return undefined
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const laravelToken = ref<string | null>(localStorage.getItem('laravel_token'))
  const isLoading = ref(false)
  const isInitialized = ref(false)
  const isAuthenticated = computed(() => !!laravelToken.value)
  const userRole = computed(() => user.value?.role || null)

  /**
   * Initialize auth state from localStorage on app startup
   */
  async function initializeAuth() {
    if (isInitialized.value) return
    
    isLoading.value = true
    try {
      if (laravelToken.value) {
        const response = await Promise.race([
          laravelAuthApi.me(),
          new Promise((_, reject) => setTimeout(() => reject(new Error('Auth initialize timeout')), 10000))
        ]) as MeResponse

        const normalizedUser = normalizeUser(response)
        user.value = normalizedUser
        initializeTheme(resolveThemePreference(normalizedUser?.dark_mode))
        
        // Persist normalized user to localStorage
        if (normalizedUser) {
          localStorage.setItem('user', JSON.stringify(normalizedUser))
        }
      }
    } catch (error) {
      console.error('Auth initialization failed:', error)
      // Clear invalid or expired token so login can proceed
      laravelToken.value = null
      user.value = null
      localStorage.removeItem('laravel_token')
      localStorage.removeItem('user')
    } finally {
      isLoading.value = false
      isInitialized.value = true
    }
  }

  /**
   * Login with Laravel backend
   */
  async function loginLaravel(email: string, password: string): Promise<LoginResponse> {
    isLoading.value = true
    try {
      const response = await laravelAuthApi.login(email, password) as LoginResponse
      const normalizedUser = normalizeUser(response)
      
      laravelToken.value = response.token
      user.value = normalizedUser
      initializeTheme(resolveThemePreference(normalizedUser?.dark_mode))
      
      // Persist to localStorage
      localStorage.setItem('laravel_token', response.token)
      if (normalizedUser) {
        localStorage.setItem('user', JSON.stringify(normalizedUser))
      }
      
      return { ...response, user: normalizedUser! }
    } catch (error) {
      console.error('Laravel login failed:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Register a new user (requires OTP verification before login)
   */
  async function registerLaravel(name: string, email: string, password: string): Promise<RegisterResponse> {
    isLoading.value = true
    try {
      const response = await laravelAuthApi.register(name, email, password) as RegisterResponse
      return response
    } catch (error) {
      console.error('Laravel register failed:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Verify email OTP after registration
   */
  async function verifyEmailOtp(email: string, otp: string): Promise<VerifyOtpResponse> {
    isLoading.value = true
    try {
      const response = await laravelAuthApi.verifyEmailOtp(email, otp) as VerifyOtpResponse
      const normalizedUser = normalizeUser(response)
      
      laravelToken.value = response.token
      user.value = normalizedUser
      initializeTheme(resolveThemePreference(normalizedUser?.dark_mode))
      
      // Persist to localStorage
      localStorage.setItem('laravel_token', response.token)
      localStorage.setItem('user', JSON.stringify(normalizedUser!))
      
      return { ...response, user: normalizedUser! }
    } catch (error) {
      console.error('OTP Verification failed:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Resend email verification OTP
   */
  async function resendEmailOtp(email: string) {
    try {
      const response = await laravelAuthApi.resendEmailOtp(email)
      return response
    } catch (error) {
      console.error('Resend OTP failed:', error)
      throw error
    }
  }

  /**
   * Request password reset OTP
   */
  async function forgotPassword(email: string): Promise<ForgotPasswordResponse> {
    isLoading.value = true
    try {
      const response = await laravelAuthApi.forgotPassword(email) as ForgotPasswordResponse
      return response
    } catch (error) {
      console.error('Forgot password failed:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Reset password with OTP
   */
  async function resetPassword(email: string, otp: string, password: string, passwordConfirmation: string) {
    isLoading.value = true
    try {
      const response = await laravelAuthApi.resetPassword(email, otp, password, passwordConfirmation)
      return response
    } catch (error) {
      console.error('Reset password failed:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Logout and clear auth state
   */
  async function logout() {
    try {
      if (laravelToken.value) {
        await laravelAuthApi.logout()
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      laravelToken.value = null
      user.value = null
      localStorage.removeItem('laravel_token')
      localStorage.removeItem('user')
    }
  }

  /**
   * Refresh JWT token
   */
  async function refreshToken(): Promise<RefreshResponse> {
    try {
      const response = await laravelAuthApi.refresh() as RefreshResponse
      if (response.token) {
        laravelToken.value = response.token
        localStorage.setItem('laravel_token', response.token)
      }
      return response
    } catch (error) {
      console.error('Token refresh failed:', error)
      throw error
    }
  }

  /**
   * Fetch current user data (for manual refresh)
   */
  async function fetchUser(): Promise<User | null> {
    try {
      if (laravelToken.value) {
        const response = await laravelAuthApi.me() as MeResponse
        const normalizedUser = normalizeUser(response)
        user.value = normalizedUser
        initializeTheme(resolveThemePreference(normalizedUser?.dark_mode))
        localStorage.setItem('user', JSON.stringify(normalizedUser!))
        return normalizedUser
      }
      return null
    } catch (error) {
      console.error('Fetch user failed:', error)
      throw error
    }
  }

  /**
   * Check if user has a specific role
   */
  function hasRole(role: string): boolean {
    if (!user.value) return false
    return user.value.roles?.includes(role) || user.value.role === role
  }

  /**
   * Check if user has a specific permission
   */
  function hasPermission(permission: string): boolean {
    if (!user.value) return false
    // Admin has all permissions
    if (user.value.roles?.includes('admin')) return true
    return user.value.permissions?.includes(permission) || false
  }

  async function updateUserProfile(payload: { name?: string; email?: string; lang?: string }) {
    const response = await laravelAuthApi.updateProfile(payload) as MeResponse
    const normalizedUser = normalizeUser(response)
    user.value = normalizedUser
    if (normalizedUser) {
      localStorage.setItem('user', JSON.stringify(normalizedUser))
    }
    return normalizedUser
  }

  async function updatePassword(payload: {
    current_password: string
    password: string
    password_confirmation: string
  }) {
    return laravelAuthApi.updatePassword(payload)
  }

  async function updateAvatar(file: File) {
    const response = await laravelAuthApi.updateAvatar(file) as MeResponse
    const normalizedUser = normalizeUser(response)
    user.value = normalizedUser
    if (normalizedUser) {
      localStorage.setItem('user', JSON.stringify(normalizedUser))
    }
    return normalizedUser
  }

  async function updatePreferences(payload: {
    dark_mode?: boolean
    lang?: string
    messenger_color?: string
  }) {
    const response = await laravelAuthApi.updatePreferences(payload) as MeResponse
    const normalizedUser = normalizeUser(response)
    user.value = normalizedUser
    if (normalizedUser) {
      localStorage.setItem('user', JSON.stringify(normalizedUser))
      if (typeof payload.dark_mode === 'boolean') {
        initializeTheme(payload.dark_mode ? 'dark' : 'light')
      }
    }
    return normalizedUser
  }

  return {
    // State
    user,
    laravelToken,
    isLoading,
    isInitialized,
    isAuthenticated,
    userRole,
    
    // Actions
    initializeAuth,
    loginLaravel,
    registerLaravel,
    verifyEmailOtp,
    resendEmailOtp,
    forgotPassword,
    resetPassword,
    logout,
    refreshToken,
    fetchUser,
    hasRole,
    hasPermission,
    updateUserProfile,
    updatePassword,
    updateAvatar,
    updatePreferences
  }
})
