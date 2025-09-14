import naive from 'naive-ui'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import '../css/app.css'
import App from './App.vue'
import { router } from './router'

const app = createApp(App)

const pinia = createPinia()

app.use(router)
app.use(naive)
app.use(pinia)

// https://www.naiveui.com/en-US/light/docs/style-conflict
const meta = document.createElement('meta')
meta.name = 'naive-ui-style'
document.head.appendChild(meta)

app.mount('#app')
