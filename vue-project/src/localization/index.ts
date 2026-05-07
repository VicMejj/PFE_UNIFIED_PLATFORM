import { computed, ref } from 'vue'
import { FRENCH_TRANSLATIONS } from '@/localization/catalog'
import {
  DEFAULT_LOCALE,
  getStoredLocale,
  normalizeLocale,
  persistLocale,
  type SupportedLocale,
} from '@/utils/localeStorage'

const TRANSLATABLE_ATTRIBUTES = ['placeholder', 'title', 'aria-label', 'alt'] as const

const appLocale = ref<SupportedLocale>(DEFAULT_LOCALE)
const intlLocale = computed(() => (appLocale.value === 'fr' ? 'fr-FR' : 'en-US'))

const originalTextByNode = new WeakMap<Text, string>()
const lastAppliedTextByNode = new WeakMap<Text, string>()
const originalAttrsByElement = new WeakMap<Element, Map<string, string>>()
const lastAppliedAttrsByElement = new WeakMap<Element, Map<string, string>>()

let observer: MutationObserver | null = null
let isApplyingTranslations = false
let applyScheduled = false

function setDocumentLanguage(locale: SupportedLocale) {
  if (typeof document === 'undefined') return
  document.documentElement.lang = locale
  document.documentElement.dir = 'ltr'
}

function translateLiteral(value: string): string {
  if (appLocale.value !== 'fr') return value

  const unreadMatch = value.match(/^(\d+)\s+unread item(s)?\.$/u)
  if (unreadMatch) {
    const count = unreadMatch[1]
    return `${count} élément${count === '1' ? '' : 's'} non lu${count === '1' ? '' : 's'}.`
  }

  const urgentItemsMatch = value.match(/^(\d+)\s+urgent items$/u)
  if (urgentItemsMatch) {
    const count = urgentItemsMatch[1]
    return `${count} élément${count === '1' ? '' : 's'} urgent${count === '1' ? '' : 's'}`
  }

  const minutesAgoMatch = value.match(/^(\d+)m ago$/u)
  if (minutesAgoMatch) {
    return `il y a ${minutesAgoMatch[1]} min`
  }

  const hoursAgoMatch = value.match(/^(\d+)h ago$/u)
  if (hoursAgoMatch) {
    return `il y a ${hoursAgoMatch[1]} h`
  }

  const daysAgoMatch = value.match(/^(\d+)d ago$/u)
  if (daysAgoMatch) {
    return `il y a ${daysAgoMatch[1]} j`
  }

  return FRENCH_TRANSLATIONS[value] ?? value
}

function translatePreservingWhitespace(value: string): string {
  const match = value.match(/^(\s*)(.*?)(\s*)$/su)
  if (!match) return translateLiteral(value)

  const [, leading, core, trailing] = match
  if (!core) return value

  return `${leading}${translateLiteral(core)}${trailing}`
}

function applyTextTranslation(node: Text) {
  const currentValue = node.textContent ?? ''

  if (!currentValue.trim()) return
  if (node.parentElement && ['SCRIPT', 'STYLE', 'NOSCRIPT'].includes(node.parentElement.tagName)) return

  const previousSource = originalTextByNode.get(node)
  const previousApplied = lastAppliedTextByNode.get(node)
  const sourceValue = !previousSource || currentValue !== previousApplied ? currentValue : previousSource
  const nextValue = appLocale.value === 'fr' ? translatePreservingWhitespace(sourceValue) : sourceValue

  originalTextByNode.set(node, sourceValue)

  if (currentValue !== nextValue) {
    node.textContent = nextValue
  }

  lastAppliedTextByNode.set(node, nextValue)
}

function applyAttributeTranslation(element: Element, attributeName: (typeof TRANSLATABLE_ATTRIBUTES)[number]) {
  const currentValue = element.getAttribute(attributeName)
  if (!currentValue) return

  const previousSources = originalAttrsByElement.get(element) ?? new Map<string, string>()
  const previousApplied = lastAppliedAttrsByElement.get(element)?.get(attributeName)
  const sourceValue =
    !previousSources.has(attributeName) || currentValue !== previousApplied
      ? currentValue
      : previousSources.get(attributeName) ?? currentValue
  const nextValue = appLocale.value === 'fr' ? translateLiteral(sourceValue) : sourceValue

  previousSources.set(attributeName, sourceValue)
  originalAttrsByElement.set(element, previousSources)

  if (currentValue !== nextValue) {
    element.setAttribute(attributeName, nextValue)
  }

  const appliedAttrs = lastAppliedAttrsByElement.get(element) ?? new Map<string, string>()
  appliedAttrs.set(attributeName, nextValue)
  lastAppliedAttrsByElement.set(element, appliedAttrs)
}

function applyTranslations() {
  if (typeof document === 'undefined' || !document.body) return

  isApplyingTranslations = true

  try {
    const textWalker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT)
    let currentTextNode = textWalker.nextNode()

    while (currentTextNode) {
      applyTextTranslation(currentTextNode as Text)
      currentTextNode = textWalker.nextNode()
    }

    const elementWalker = document.createTreeWalker(document.body, NodeFilter.SHOW_ELEMENT)
    let currentElement = elementWalker.nextNode()

    while (currentElement) {
      for (const attributeName of TRANSLATABLE_ATTRIBUTES) {
        applyAttributeTranslation(currentElement as Element, attributeName)
      }
      currentElement = elementWalker.nextNode()
    }
  } finally {
    isApplyingTranslations = false
  }
}

function scheduleApplyTranslations() {
  if (typeof window === 'undefined' || applyScheduled) return

  applyScheduled = true
  window.requestAnimationFrame(() => {
    applyScheduled = false
    applyTranslations()
  })
}

export function initializeLocalization(initialLocale?: string | null) {
  const locale = normalizeLocale(initialLocale ?? getStoredLocale())
  appLocale.value = locale
  setDocumentLanguage(locale)
  persistLocale(locale)
}

export function startLocalizationObserver() {
  if (typeof window === 'undefined' || typeof document === 'undefined' || observer || !document.body) return

  observer = new MutationObserver(() => {
    if (isApplyingTranslations) return
    scheduleApplyTranslations()
  })

  observer.observe(document.body, {
    subtree: true,
    childList: true,
    characterData: true,
    attributes: true,
    attributeFilter: [...TRANSLATABLE_ATTRIBUTES],
  })

  scheduleApplyTranslations()
}

export function setAppLocale(locale?: string | null, options: { persist?: boolean } = {}) {
  const nextLocale = normalizeLocale(locale)

  appLocale.value = nextLocale
  setDocumentLanguage(nextLocale)

  if (options.persist !== false) {
    persistLocale(nextLocale)
  }

  scheduleApplyTranslations()
}

export function useAppLocale() {
  return {
    locale: appLocale,
    intlLocale,
    setLocale: setAppLocale,
  }
}
