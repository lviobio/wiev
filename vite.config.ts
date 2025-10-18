import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import AutoImport from 'unplugin-auto-import/vite'
import { NaiveUiResolver } from 'unplugin-vue-components/resolvers'
import Components from 'unplugin-vue-components/vite'
import { defineConfig, loadEnv } from 'vite'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    server: {
      port: env.EXPOSE_VITE_PORT,
      hmr: {
        host: 'localhost',
      },
    },
    plugins: [
      laravel({
        input: ['resources/js/app.ts'],
        ssr: 'resources/js/ssr.ts',
        refresh: true,
      }),
      tailwindcss(),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
      vueJsx(),
      Components({
        deep: true,
        resolvers: [NaiveUiResolver()],
        collapseSamePrefixes: true,
        dirs: ['resources/js/components'],
        allowOverrides: true,
      }),
      AutoImport({
        dts: './auto-imports.d.ts',
        vueTemplate: true,
        dirs: ['resources/js/utils/**', 'resources/js/composables/**'],
        imports: [
          'vue',
          {
            'vue-router': ['useRoute', 'useRouter'],
            'naive-ui': ['useDialog', 'useMessage', 'useNotification', 'useLoadingBar'],
          },
        ],
      }),
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './resources/js'),
      },
    },
  }
})
