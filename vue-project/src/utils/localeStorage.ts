export const DEFAULT_LOCALE = 'en' as const
export const SUPPORTED_LOCALES = ['en', 'fr'] as const
export const APP_LOCALE_STORAGE_KEY = 'app_locale'

export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number]

export function normalizeLocale(locale?: string | null): SupportedLocale {
  return locale === 'fr' ? 'fr' : DEFAULT_LOCALE
}

export function getStoredLocale(): SupportedLocale {
  if (typeof window === 'undefined') return DEFAULT_LOCALE
  return normalizeLocale(window.localStorage.getItem(APP_LOCALE_STORAGE_KEY))
}

export function persistLocale(locale?: string | null) {
  if (typeof window === 'undefined') return
  window.localStorage.setItem(APP_LOCALE_STORAGE_KEY, normalizeLocale(locale))
}
