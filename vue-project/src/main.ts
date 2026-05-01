import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import { laravelApi } from '@/api/http'
import { initializeTheme } from '@/composables/useTheme'
import LaravelEcho from 'laravel-echo'
import Pusher from 'pusher-js'

import App from './App.vue'
import router from './router'

initializeTheme()

declare global {
  interface Window {
    Echo: any
    Pusher: typeof Pusher
  }
}

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(VueApexCharts)

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
const currentPort = typeof window !== 'undefined' ? Number(window.location.port || (window.location.protocol === 'https:' ? 443 : 80)) : null
const useDevProxyForReverb = import.meta.env.DEV && typeof window !== 'undefined' && currentPort === 5173
const reverbWsHost = useDevProxyForReverb ? currentHostname : resolvedReverbHost
const reverbWsPort = useDevProxyForReverb ? currentPort : Number(import.meta.env.VITE_REVERB_PORT || 8080)

if (reverbKey) {
  window.Pusher = Pusher

  const echo = new LaravelEcho({
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

  window.Echo = echo
  app.config.globalProperties.$echo = echo
}

app.mount('#app')
