export type ChatMessageSegment = {
  key: string
  kind: 'status' | 'text' | 'heading' | 'bullet' | 'code'
  text: string
  bold?: boolean
}

const STATUS_LINE_RE = /^\[[^\]]+\]$/
const HEADING_RE = /^#{1,3}\s+(.+)$/
const BULLET_RE = /^[-•]\s+(.+)$/
const CODE_INLINE_RE = /`([^`]+)`/g
const BOLD_RE = /\*\*([^*]+)\*\*/g

/**
 * Parse a chat message into renderable segments.
 * Supports: status lines [xyz], headings (#), bullets (-), bold (**text**), inline code (`code`).
 */
export function parseChatMessage(text: string): ChatMessageSegment[] {
  const normalized = String(text ?? '').replace(/\r\n/g, '\n')
  const lines = normalized.split('\n')
  const segments: ChatMessageSegment[] = []

  lines.forEach((rawLine, index) => {
    const line = rawLine.trimEnd()
    if (!line.trim()) return

    const trimmed = line.trim()

    // Status line: [thinking], [checking platform data], etc.
    if (STATUS_LINE_RE.test(trimmed)) {
      segments.push({ key: `${index}-status`, kind: 'status', text: trimmed })
      return
    }

    // Heading: # Title, ## Subtitle, ### Sub-subtitle
    const headingMatch = trimmed.match(HEADING_RE)
    if (headingMatch) {
      segments.push({ key: `${index}-heading`, kind: 'heading', text: headingMatch[1] })
      return
    }

    // Bullet point: - item or • item
    const bulletMatch = trimmed.match(BULLET_RE)
    if (bulletMatch) {
      segments.push({ key: `${index}-bullet`, kind: 'bullet', text: cleanInlineFormatting(bulletMatch[1]) })
      return
    }

    // Regular text (with inline bold/code cleaned)
    segments.push({
      key: `${index}-${trimmed.slice(0, 20)}`,
      kind: 'text',
      text: cleanInlineFormatting(line),
      bold: /^\*\*[^*]+\*\*:?$/.test(trimmed),
    })
  })

  if (segments.length > 0) return segments

  const fallback = normalized.trim()
  return fallback
    ? [{ key: 'fallback-0', kind: STATUS_LINE_RE.test(fallback) ? 'status' : 'text', text: fallback }]
    : []
}

/** Strip markdown bold markers and inline code backticks for display. */
function cleanInlineFormatting(text: string): string {
  return text.replace(BOLD_RE, '$1').replace(CODE_INLINE_RE, '$1')
}

export function looksPlatformRelatedMessage(text: string): boolean {
  const lower = String(text ?? '').toLowerCase()

  return [
    'employee',
    'staff',
    'department',
    'designation',
    'branch',
    'leave',
    'attendance',
    'timesheet',
    'policy',
    'payroll',
    'salary',
    'benefit',
    'insurance',
    'claim',
    'contract',
    'dashboard',
    'platform',
    'system',
    'role',
    'user'
  ].some((keyword) => lower.includes(keyword))
}
