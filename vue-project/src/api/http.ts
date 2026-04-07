import axios from 'axios'

type ThemeAwareUser = {
  id?: number
}

function redirectToLogin() {
  if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
    window.location.href = '/login'
  }
}

function attachAuthToken(config: any) {
  const token = localStorage.getItem('laravel_token')
  if (token) {
    config.headers = config.headers ?? {}
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
}

function handleUnauthorized(error: any) {
  if (error.response?.status === 401) {
    localStorage.removeItem('laravel_token')
    redirectToLogin()
  }

  return Promise.reject(error)
}

const laravelBaseUrl = import.meta.env.VITE_LARAVEL_API_URL || '/api'

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
  baseURL: import.meta.env.VITE_DJANGO_API_URL,
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

export function getAvatarUrl(user: { avatar_url?: string | null; avatar?: string | null } | null | undefined) {
  if (!user) return ''
  if (user.avatar_url) return user.avatar_url
  if (!user.avatar) return ''
  if (user.avatar.startsWith('http://') || user.avatar.startsWith('https://')) return user.avatar

  const apiUrl = import.meta.env.VITE_LARAVEL_API_URL || ''
  const origin = apiUrl.replace(/\/api\/?$/, '')
  return `${origin}/${user.avatar.replace(/^\/+/, '')}`
}

export function getUserReadNotificationsKey(user: ThemeAwareUser | null | undefined) {
  return `notifications_read_${user?.id ?? 'guest'}`
}
