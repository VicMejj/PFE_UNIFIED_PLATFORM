import axios from 'axios'
import { clearStoredAuth, getStoredToken } from '@/utils/authStorage'

type ThemeAwareUser = {
  id?: number
}

function redirectToLogin() {
  if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
    window.location.href = '/login'
  }
}

function attachAuthToken(config: any) {
  const token = getStoredToken()
  if (token) {
    config.headers = config.headers ?? {}
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
}

function handleUnauthorized(error: any) {
  if (error.response?.status === 401) {
    clearStoredAuth()
    redirectToLogin()
  }

  return Promise.reject(error)
}

const laravelBaseUrl = import.meta.env.VITE_LARAVEL_API_URL || '/api'
const djangoBaseUrl = import.meta.env.VITE_DJANGO_API_URL || '/django-api/api'

export const laravelApi = axios.create({
  baseURL: laravelBaseUrl,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

laravelApi.interceptors.request.use(attachAuthToken)
laravelApi.interceptors.response.use((response) => response, handleUnauthorized)

export const djangoApi = axios.create({
  baseURL: djangoBaseUrl,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

djangoApi.interceptors.request.use(attachAuthToken)
djangoApi.interceptors.response.use((response) => response, handleUnauthorized)

export function unwrapResponse<T>(response: any): T {
  if (response?.data?.data !== undefined) {
    return response.data.data as T
  }

  return response.data as T
}

export function unwrapItems<T>(payload: any): T[] {
  if (Array.isArray(payload)) return payload as T[]
  if (Array.isArray(payload?.data)) return payload.data as T[]
  return []
}

export function isNetworkOrServerUnavailable(error: any) {
  const status = error?.response?.status
  const message = String(error?.message || '').toLowerCase()

  return (
    error?.code === 'ERR_NETWORK' ||
    error?.code === 'ECONNABORTED' ||
    status === 502 ||
    status === 503 ||
    status === 504 ||
    message.includes('network error') ||
    message.includes('connection refused') ||
    message.includes('timeout')
  )
}

export function getAvatarUrl(user: { avatar_url?: string | null; avatar?: string | null } | null | undefined) {
  if (!user) return ''
  if (user.avatar_url) {
    try {
      const parsed = new URL(user.avatar_url, window.location.origin)
      if (parsed.hostname === '127.0.0.1' || parsed.hostname === 'localhost') {
        return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`
      }
      return parsed.toString()
    } catch {
      return user.avatar_url
    }
  }
  if (!user.avatar) return ''
  if (user.avatar.startsWith('http://') || user.avatar.startsWith('https://')) return user.avatar
  const path = user.avatar.replace(/^\/+/, '')
  if (path.startsWith('uploads/')) {
    return `/${path}`
  }
  return `/${path}`
}

export function getUserReadNotificationsKey(user: ThemeAwareUser | null | undefined) {
  return `notifications_read_${user?.id ?? 'guest'}`
}
