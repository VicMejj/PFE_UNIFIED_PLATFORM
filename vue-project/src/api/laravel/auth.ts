import { laravelApi } from '@/api/http'
import { clearStoredAuth } from '@/utils/authStorage'

/**
 * Extract data from Laravel's standardized response envelope.
 * Laravel returns: { success: true, data: {...}, message: '...', timestamp: '...' }
 * Frontend expects: { token, user, roles, permissions, ... }
 */
function unwrapResponse(response: any) {
  // If response has the Laravel envelope, extract the data
  if (response.data && response.data.data !== undefined) {
    return response.data.data
  }
  // Otherwise return the raw data
  return response.data
}

export default {
  async login(email: string, password: string) {
    const response = await laravelApi.post('/auth/login', {
      email,
      password
    })
    return unwrapResponse(response)
  },

  async register(name: string, email: string, password: string) {
    const response = await laravelApi.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation: password // Laravel requires this field since rule is 'confirmed'
    })
    return unwrapResponse(response)
  },

  async verifyEmailOtp(email: string, otp: string) {
    const response = await laravelApi.post('/auth/verify-email-otp', {
      email,
      otp
    })
    return unwrapResponse(response)
  },

  async resendEmailOtp(email: string) {
    const response = await laravelApi.post('/auth/resend-email-otp', { email })
    return unwrapResponse(response)
  },

  async forgotPassword(email: string) {
    const response = await laravelApi.post('/auth/forgot-password', { email })
    return unwrapResponse(response)
  },

  async resetPassword(email: string, otp: string, password: string, passwordConfirmation: string) {
    const response = await laravelApi.post('/auth/reset-password', {
      email,
      otp,
      password,
      password_confirmation: passwordConfirmation
    })
    return unwrapResponse(response)
  },

  async logout() {
    try {
      const response = await laravelApi.post('/core/auth/logout')
      return unwrapResponse(response)
    } finally {
      clearStoredAuth()
    }
  },

  async me() {
    const response = await laravelApi.get('/core/auth/me')
    return unwrapResponse(response)
  },

  async refresh() {
    const response = await laravelApi.post('/core/auth/refresh')
    return unwrapResponse(response)
  },

  async updateProfile(payload: { name?: string; email?: string; lang?: string }) {
    const response = await laravelApi.patch('/core/auth/profile', payload)
    return unwrapResponse(response)
  },

  async updatePassword(payload: {
    current_password: string
    password: string
    password_confirmation: string
  }) {
    const response = await laravelApi.patch('/core/auth/password', payload)
    return unwrapResponse(response)
  },

  async updateAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)

    const response = await laravelApi.post('/core/auth/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    return unwrapResponse(response)
  },

  async updatePreferences(payload: { dark_mode?: boolean; lang?: string; messenger_color?: string }) {
    const response = await laravelApi.patch('/core/auth/preferences', payload)
    return unwrapResponse(response)
  }
}
