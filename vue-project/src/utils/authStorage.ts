const TOKEN_KEY = 'laravel_token'
const USER_KEY = 'user'
const REMEMBER_ME_KEY = 'auth_remember_me'
const REMEMBERED_EMAIL_KEY = 'remembered_email'

type AuthStorage = Pick<Storage, 'getItem' | 'setItem' | 'removeItem'>

type PersistAuthSessionArgs = {
  token: string
  user?: unknown
  rememberMe: boolean
}

function getPersistentStorage(): AuthStorage | null {
  if (typeof window === 'undefined') return null
  return window.localStorage
}

function getSessionStorage(): AuthStorage | null {
  if (typeof window === 'undefined') return null
  return window.sessionStorage
}

function clearKey(key: string) {
  getPersistentStorage()?.removeItem(key)
  getSessionStorage()?.removeItem(key)
}

export function getStoredToken(): string | null {
  return getPersistentStorage()?.getItem(TOKEN_KEY) || getSessionStorage()?.getItem(TOKEN_KEY) || null
}

export function getStoredUser(): string | null {
  return getPersistentStorage()?.getItem(USER_KEY) || getSessionStorage()?.getItem(USER_KEY) || null
}

export function getRememberMePreference(): boolean {
  return getPersistentStorage()?.getItem(REMEMBER_ME_KEY) === 'true'
}

export function getRememberedEmail(): string {
  return getPersistentStorage()?.getItem(REMEMBERED_EMAIL_KEY) || ''
}

export function persistRememberMePreference(rememberMe: boolean) {
  const storage = getPersistentStorage()
  if (!storage) return
  storage.setItem(REMEMBER_ME_KEY, JSON.stringify(rememberMe))
}

export function persistRememberedEmail(email: string, rememberMe: boolean) {
  const storage = getPersistentStorage()
  if (!storage) return
  if (rememberMe && email.trim()) {
    storage.setItem(REMEMBERED_EMAIL_KEY, email.trim())
    return
  }
  storage.removeItem(REMEMBERED_EMAIL_KEY)
}

export function persistAuthSession({ token, user, rememberMe }: PersistAuthSessionArgs) {
  const storage = rememberMe ? getPersistentStorage() : getSessionStorage()
  if (!storage) return

  clearStoredAuth()
  persistRememberMePreference(rememberMe)
  storage.setItem(TOKEN_KEY, token)
  if (user !== undefined) {
    storage.setItem(USER_KEY, JSON.stringify(user))
  }
}

export function persistStoredUser(user: unknown) {
  const token = getStoredToken()
  if (!token) return

  const storage = getPersistentStorage()?.getItem(TOKEN_KEY) ? getPersistentStorage() : getSessionStorage()
  storage?.setItem(USER_KEY, JSON.stringify(user))
}

export function clearStoredAuth() {
  clearKey(TOKEN_KEY)
  clearKey(USER_KEY)
}
