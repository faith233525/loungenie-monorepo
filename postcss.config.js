/**
 * PostCSS Configuration
 * File: postcss.config.js
 * 
 * Optimizes CSS for production:
 * - Autoprefixer for cross-browser support
 * - CSSnano for minification
 * - PurgeCSS for unused CSS removal
 * 
 * @package PoolSafe_Portal
 * @since 3.0.0
 */

export default {
    plugins: {
        // Add vendor prefixes automatically
        autoprefixer: {
            overrideBrowserslist: [
                'last 2 versions',
                '> 1%',
                'not dead'
            ]
        },

        // Minify and optimize CSS
        cssnano: {
            preset: ['default', {
                discardComments: {
                    removeAll: true,
                },
                normalizeUnicode: false,
            }]
        },

        // Remove unused CSS (be careful with dynamic classes)
        '@fullhuman/postcss-purgecss': {
            content: [
                './views/**/*.php',
                './js/**/*.js',
                './admin/**/*.php',
            ],
            safelist: {
                standard: [
                    /^--psp/,
                    /^\.psp/,
                    /^\.wp/,
                    /^\.btn/,
                    /^\.badge/,
                    /^\.card/,
                    /^\.form/,
                    /^\.table/,
                    /^\.alert/,
                    /^\.modal/,
                    /^\.nav/,
                    /^\.tab/,
                    /^\.sm:/,
                    /^\.md:/,
                    /^\.lg:/,
                    /^\.xl:/,
                ]
            }
        }
    }
};
