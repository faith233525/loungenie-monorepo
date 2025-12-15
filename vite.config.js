import { defineConfig } from 'vite';
import compression from 'vite-plugin-compression';
import path from 'path';

export default defineConfig({
    root: '.',
    base: '/wp-content/plugins/wp-poolsafe-portal/',

    build: {
        outDir: 'dist',
        assetsDir: 'assets',
        manifest: true,
        emptyOutDir: true,
        minify: 'terser',
        sourcemap: false,

        rollupOptions: {
            input: {
                'psp-portal': 'js/psp-portal-app.js',
                'api-client': 'js/api-client.js',
            },

            output: {
                entryFileNames: 'assets/[name].[hash].js',
                chunkFileNames: 'assets/chunks/[name].[hash].js',
                assetFileNames: ({ name }) => {
                    const ext = name ? name.substring(name.lastIndexOf('.')) : '';
                    if (ext === '.css') {
                        return 'assets/css/[name].[hash][extname]';
                    }
                    if (ext === '.svg' || ext === '.png' || ext === '.jpg' || ext === '.jpeg' || ext === '.gif' || ext === '.webp') {
                        return 'assets/images/[name].[hash][extname]';
                    }
                    return 'assets/[name].[hash][extname]';
                },
                format: 'es',
                exports: 'named'
            }
        },

        terserOptions: {
            compress: {
                drop_console: true,
                pure_funcs: ['console.log', 'console.debug'],
                unsafe: true,
            },
            mangle: {
                reserved: ['pspApi', 'PSPUIHelpers', 'PSPApiClient'],
            },
            format: {
                comments: false,
            }
        },

        target: ['es2015', 'edge88', 'firefox78', 'chrome90', 'safari12'],
        cssCodeSplit: false,
        reportCompressedSize: true,
        chunkSizeWarningLimit: 500,
    },

    plugins: [
        compression({
            verbose: true,
            disable: false,
            threshold: 10240,
            algorithm: 'gzip',
            ext: '.gz',
        }),
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, './js'),
            '@css': path.resolve(__dirname, './css'),
            '@views': path.resolve(__dirname, './views'),
        },
        extensions: ['.js', '.json', '.mjs'],
    },

    server: {
        port: 3000,
        strictPort: false,
        cors: true,
        proxy: {
            '/wp-json': {
                target: 'http://localhost',
                changeOrigin: true,
                secure: false,
            },
        },
    },

    preview: {
        port: 4173,
        strictPort: false,
    },

    css: {
        postcss: './postcss.config.js',
        preprocessorOptions: {
            scss: {
                additionalData: '@import "css/variables.css";'
            }
        }
    }
});
