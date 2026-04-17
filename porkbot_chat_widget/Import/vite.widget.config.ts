import { fileURLToPath } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import cssInjectedByJs from 'vite-plugin-css-injected-by-js';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  return {
    plugins: [react(), cssInjectedByJs()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },
    define: {
      'import.meta.env.PUBLIC_PORKBOT_API_BASE_URL': JSON.stringify(env.PUBLIC_PORKBOT_API_BASE_URL ?? ''),
      'import.meta.env.PUBLIC_PORKBOT_API_PREFIX': JSON.stringify(env.PUBLIC_PORKBOT_API_PREFIX ?? '/api'),
      'import.meta.env.PUBLIC_PORKBOT_DEFAULT_MODE': JSON.stringify(env.PUBLIC_PORKBOT_DEFAULT_MODE ?? 'widget'),
      'import.meta.env.PUBLIC_PORKBOT_OPEN_BY_DEFAULT': JSON.stringify(env.PUBLIC_PORKBOT_OPEN_BY_DEFAULT ?? 'false'),
      'import.meta.env.DEV': JSON.stringify(mode === 'development'),
      'import.meta.env.PROD': JSON.stringify(mode === 'production'),
      'import.meta.env.MODE': JSON.stringify(mode),
      'import.meta.env.SSR': JSON.stringify(false),
      'import.meta.env.BASE_URL': JSON.stringify('/'),
      'process.env.NODE_ENV': JSON.stringify(mode === 'development' ? 'development' : 'production')
    },
    build: {
      outDir: 'dist-widget',
      assetsInlineLimit: 100_000,
      lib: {
        entry: fileURLToPath(new URL('./src/widget.tsx', import.meta.url)),
        name: 'PorkbotWidget',
        formats: ['iife'],
        fileName: () => 'widget.js'
      },
      rollupOptions: {
        output: {
          inlineDynamicImports: true
        }
      }
    }
  };
});
