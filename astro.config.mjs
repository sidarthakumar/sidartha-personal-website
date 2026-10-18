import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://sidarthakumar.com',
  output: 'static',
  outDir: './site-dist',
  trailingSlash: 'always',
  build: { format: 'directory' },
});
