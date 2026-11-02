import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { fr } from 'vuetify/locale'

export default createVuetify({
  components,
  directives,
  locale: { locale: 'fr', fallback: 'fr', messages: { fr } },
})
