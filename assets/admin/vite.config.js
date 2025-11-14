import { defineConfig } from 'vite'
import path from 'path'

export default defineConfig({
  root: '.',
  build: {
    outDir: './build',
    emptyOutDir: true,
    rollupOptions: {
      input: './src/index.tsx',
      output: { entryFileNames: 'index.js', assetFileNames: '[name][extname]' }
    }
  },
  esbuild: { jsxFactory: 'React.createElement', jsxFragment: 'React.Fragment' },
  resolve: { alias: { '@': path.resolve(__dirname, './src') } }
})
