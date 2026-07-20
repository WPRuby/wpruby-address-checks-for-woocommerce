import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

/**
 * Build the Vue admin app into assets/admin/dist with stable, unhashed
 * filenames (app.js / app.css) so WordPress can enqueue them directly.
 */
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./assets/admin/vue', import.meta.url)),
    },
  },
  build: {
    outDir: 'assets/admin/dist',
    emptyOutDir: true,
    cssCodeSplit: false,
    manifest: false,
    rollupOptions: {
      input: fileURLToPath(new URL('./assets/admin/vue/main.js', import.meta.url)),
      output: {
        format: 'iife',
        name: 'WPRubyAddressChecksAdmin',
        entryFileNames: 'app.js',
        chunkFileNames: 'app-[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'app.css';
          }
          return 'app-[name][extname]';
        },
      },
    },
  },
});
