import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5174,
    strictPort: true,
    cors: true,
    proxy: {
      // Proxy API requests to Laravel backend
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        ws: true,
        configure: (proxy, _options) => {
          proxy.on('proxyReq', (proxyReq, req, _res) => {
            // Forward cookies
            const cookieHeader = req.headers.cookie
            if (cookieHeader) {
              proxyReq.setHeader('cookie', cookieHeader)
            }
          })
        },
      },
      // Only proxy the logout API endpoint
      '/logout': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        configure: (proxy, _options) => {
          proxy.on('proxyReq', (proxyReq, req, _res) => {
            const cookieHeader = req.headers.cookie
            if (cookieHeader) {
              proxyReq.setHeader('cookie', cookieHeader)
            }
          })
        },
      },
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        configure: (proxy, _options) => {
          proxy.on('proxyReq', (proxyReq, req, _res) => {
            const cookieHeader = req.headers.cookie
            if (cookieHeader) {
              proxyReq.setHeader('cookie', cookieHeader)
            }
          })
        },
      },
      // Proxy storage files
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    manifest: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'index.html'),
      },
    },
  },
})