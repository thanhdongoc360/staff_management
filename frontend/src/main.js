import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { getEcho } from './echo'

// Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'

// Bootstrap JS
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

// Custom styles
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize Laravel Echo and expose on global properties
try {
  const echo = getEcho()
  if (echo) {
    app.config.globalProperties.$echo = echo
  }
} catch (e) {
  console.warn('Echo init skipped:', e)
}

app.mount('#app')
