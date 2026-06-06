import { createApp } from 'vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import router from './router.js'
import App from './App.vue'

const app = createApp(App)

// @nextcloud/vue v9 does NOT register t()/n() automatically
app.config.globalProperties.t = t
app.config.globalProperties.n = n

app.use(router)
app.mount('#git-root')
