import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import { initializeLocalization, startLocalizationObserver } from '@/localization'
import { initializeTheme } from '@/composables/useTheme'

import App from './App.vue'
import router from './router'

initializeTheme()
initializeLocalization()

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(VueApexCharts)

app.mount('#app')
startLocalizationObserver()
