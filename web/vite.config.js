import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    proxy: {
      // Laravel API (php artisan serve)
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      // Sanctum SPA: cookie CSRF y sesión
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      // Archivos públicos (fotos de animales, comprobantes)
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
