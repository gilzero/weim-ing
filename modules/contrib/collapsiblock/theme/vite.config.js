// eslint-disable-next-line
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => {
  return {
    build: {
      manifest: true,
      rollupOptions: {
        external: ['Drupal', 'once'],
        input: 'src/js/collapsiblock.js',
        output: {
          assetFileNames: (assetInfo) => {
            return assetInfo.name;
          },
          entryFileNames: (assetInfo) => {
            return `${assetInfo.name}.js`;
          },
          format: 'esm',
        },
      },
    },
    css: { devSourcemap: true },
    define: {
      'process.env.NODE_ENV':
        mode === 'production' ? '"production"' : '"development"',
    },
  };
});
