import LaravelEcho from 'laravel-echo'
import Pusher from 'pusher-js'
import { laravelApi } from '@/api/http'
import { getStoredToken } from '@/utils/authStorage'

declare global {
  interface Window {
    Echo?: any
    Pusher?: typeof Pusher
  }
}

const reverbKey = String(import.meta.env.VITE_REVERB_APP_KEY || '').trim()
const reverbScheme = String(import.meta.env.VITE_REVERB_SCHEME || 'http').trim().toLowerCase()
const useTls = reverbScheme === 'https'
const configuredReverbHost = String(import.meta.env.VITE_REVERB_HOST || '127.0.0.1').trim()
const loopbackHosts = new Set(['127.0.0.1', 'localhost', '0.0.0.0', '::1'])
const currentHostname = typeof window !== 'undefined' ? window.location.hostname : ''
const currentHostIsLoopback = loopbackHosts.has(currentHostname)
const configuredHostIsLoopback = loopbackHosts.has(configuredReverbHost)
const resolvedReverbHost = configuredHostIsLoopback && currentHostname && !currentHostIsLoopback
  ? currentHostname
  : configuredReverbHost === '0.0.0.0'
    ? '127.0.0.1'
    : configuredReverbHost
const currentPort = typeof window !== 'undefined'
  ? Number(window.location.port || (window.location.protocol === 'https:' ? 443 : 80))
  : null
const useDevProxyForReverb = import.meta.env.DEV && typeof window !== 'undefined' && currentPort === 5173
const reverbWsHost = useDevProxyForReverb ? currentHostname : resolvedReverbHost
const reverbWsPort = useDevProxyForReverb ? currentPort : Number(import.meta.env.VITE_REVERB_PORT || 8080)

export function canInitializeEcho() {
  return typeof window !== 'undefined' && Boolean(reverbKey) && Boolean(getStoredToken())
}

export function ensureEcho() {
  if (typeof window === 'undefined') return null
  if (window.Echo) return window.Echo
  if (!canInitializeEcho()) return null

  window.Pusher = Pusher
  window.Echo = new LaravelEcho({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbWsHost,
    wsPort: reverbWsPort,
    wssPort: reverbWsPort,
    forceTLS: useTls,
    enabledTransports: [useTls ? 'wss' : 'ws'],
    authorizer: (channel) => ({
      authorize: async (socketId, callback) => {
        try {
          const response = await laravelApi.post('/broadcasting/auth', {
            socket_id: socketId,
            channel_name: channel.name,
          })

          ;(callback as any)(false, response.data)
        } catch (error) {
          ;(callback as any)(true, error)
        }
      },
    }),
  })

  return window.Echo
}
