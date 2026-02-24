import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  root: 'src',
  base: '/assets/dist/',

  build: {
    outDir: '../assets/dist',
    emptyDirOnBuild: true,
    rollupOptions: {
      input: {
        // Main entry points
        main: resolve(__dirname, 'src/js/main.js'),
        // Page-specific entries
        index: resolve(__dirname, 'src/js/index.js'),
        gallery: resolve(__dirname, 'src/js/gallery.js'),
        sissylonglegs: resolve(__dirname, 'src/js/sissylonglegs.js'),
        content: resolve(__dirname, 'src/js/content.js'),
        porn: resolve(__dirname, 'src/js/porn.js'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },

  server: {
    port: 3000,
    // Proxy PHP requests to Apache
    proxy: {
      // Proxy .php files to Apache
      '*.php': {
        target: 'http://localhost:80',
        changeOrigin: true,
      },
    },
  },

  css: {
    postcss: './postcss.config.js',
  },
});
