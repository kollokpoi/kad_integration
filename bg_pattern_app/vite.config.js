import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  root: '.', // корневая папка проекта, где лежит index.html
  base: './',
  build: {
    outDir: 'dist', // или другой путь, если нужно
    emptyOutDir: true,
    external: ['chart.js/auto'],
    rollupOptions:{
      external:[]
    }
  },
});
