export default defineNuxtConfig({
  modules: ['@nuxtjs/tailwindcss', '@pinia/nuxt'],
  
  devServer: {
    host: '0.0.0.0',
    port: 3000,
    https: {
      key: './localhost+2-key.pem',
      cert: './localhost+2.pem'
    }
  },

  runtimeConfig: {
    public: {
      // Dynamic IP will be set by auto_configure_network.ps1
      apiBase: 'http://192.168.254.108:8000/api',
    }
  },

  app: {
    head: {
      title: 'Charmafa',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' }
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/charmafa-logo.png' }
      ]
    }
  },

  css: ['~/assets/css/main.css'], 

  devtools: { enabled: true }
})
















































































































































