import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import laravelAuthApi from '@/api/laravel/auth'
import { initializeTheme } from '@/composables/useTheme'
import { isNetworkOrServerUnavailable } from '@/api/http'
import {
  clearStoredAuth,
  getRememberMePreference,
  getStoredUser,
  getStoredToken,
  persistAuthSession,
  persistStoredUser
} from '@/utils/authStorage'

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
  employee_id?: number | null
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
  const employeeId = apiResponse.employee_id ?? user?.employee_id ?? null
  
  if (!user) return null
  
  return {
    ...user,
    employee_id: employeeId,
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

function loadStoredUser(): User | null {
  const rawUser = getStoredUser()
  if (!rawUser) return null

  try {
    return JSON.parse(rawUser) as User
  } catch {
    return null
  }
}

/** Only drop stored credentials when the server rejected the token — not on 5xx or network errors. */
function shouldClearSessionOnMeFailure(error: unknown): boolean {
  const status = (error as { response?: { status?: number } })?.response?.status
  return status === 401 || status === 403
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(loadStoredUser())
  const laravelToken = ref<string | null>(getStoredToken())
  const isLoading = ref(false)
  const isInitialized = ref(false)
  let initializePromise: Promise<void> | null = null
  const isAuthenticated = computed(() => !!laravelToken.value)
  const userRole = computed(() => user.value?.role || null)

  /**
   * Initialize auth state from localStorage on app startup
   */
  async function initializeAuth() {
    if (isInitialized.value) return
    if (initializePromise) return initializePromise
    
    initializePromise = (async () => {
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
          
          if (normalizedUser) {
            persistStoredUser(normalizedUser)
          }
        }
      } catch (error) {
        if (!isNetworkOrServerUnavailable(error)) {
          console.error('Auth initialization failed:', error)
        }
        if (shouldClearSessionOnMeFailure(error)) {
          laravelToken.value = null
          user.value = null
          clearStoredAuth()
        }
      } finally {
        isLoading.value = false
        isInitialized.value = true
        initializePromise = null
      }
    })()

    return initializePromise
  }

  /**
   * Login with Laravel backend
   */
  async function loginLaravel(email: string, password: string, rememberMe = false): Promise<LoginResponse> {
    isLoading.value = true
    try {
      const response = await laravelAuthApi.login(email, password) as LoginResponse
      const normalizedUser = normalizeUser(response)
      
      laravelToken.value = response.token
      user.value = normalizedUser
      initializeTheme(resolveThemePreference(normalizedUser?.dark_mode))
      
      if (normalizedUser) {
        persistAuthSession({ token: response.token, user: normalizedUser, rememberMe })
      } else {
        persistAuthSession({ token: response.token, rememberMe })
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
      
      persistAuthSession({
        token: response.token,
        user: normalizedUser!,
        rememberMe: getRememberMePreference()
      })
      
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
      clearStoredAuth()
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
        persistAuthSession({ token: response.token, user: user.value ?? undefined, rememberMe: getRememberMePreference() })
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
        persistStoredUser(normalizedUser!)
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
      persistStoredUser(normalizedUser)
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
      persistStoredUser(normalizedUser)
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
      persistStoredUser(normalizedUser)
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
