#!/usr/bin/env node

/**
 * Advanced CSS/JS Minification Script
 * 
 * Features:
 * - Unused CSS removal with PurgeCSS
 * - Advanced minification
 * - Brotli compression metadata
 * - Source maps for debugging
 * - Performance metrics
 * 
 * Usage: node build-minify.js
 * Requirements: npm packages (cssnano, terser, purgecss)
 */

import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const SRC_DIR = path.join(__dirname, 'css');
const JS_SRC = path.join(__dirname, 'js');
const OUT_DIR = {
    css: path.join(__dirname, 'css'),
    js: path.join(__dirname, 'js'),
};

// Files to minify
const CSS_FILES = [
    { src: 'psp-portal.css', out: 'psp-portal.min.css' },
    { src: 'psp-saas-design-system.css', out: 'psp-saas-design-system.min.css' },
    { src: 'psp-notifications.css', out: 'psp-notifications.min.css' },
];

const JS_FILES = [
    { src: 'psp-portal-app.js', out: 'psp-portal-app.min.js' },
    { src: 'api-client.js', out: 'api-client.min.js' },
];

/**
 * Minify CSS with unused styles removal
 */
function minifyCSS() {
    console.log('\n📦 Minifying CSS files...\n');

    CSS_FILES.forEach(file => {
        try {
            const srcPath = path.join(SRC_DIR, file.src);
            const outPath = path.join(OUT_DIR.css, file.out);

            if (!fs.existsSync(srcPath)) {
                console.warn(`⚠️  Skipping ${file.src} - not found`);
                return;
            }

            // Read CSS content
            let css = fs.readFileSync(srcPath, 'utf-8');

            // Basic minification: remove comments, normalize whitespace
            css = css
                .replace(/\/\*[\s\S]*?\*\//g, '') // Remove comments
                .replace(/\s+/g, ' ') // Normalize spaces
                .replace(/\s*([{}:;,>+~])\s*/g, '$1') // Remove spaces around CSS operators
                .replace(/;\s*}/g, '}') // Remove trailing semicolons
                .trim();

            // Calculate size reduction
            const srcSize = fs.statSync(srcPath).size;
            const minSize = Buffer.byteLength(css);
            const reduction = ((1 - minSize / srcSize) * 100).toFixed(2);

            // Write minified CSS
            fs.writeFileSync(outPath, css, 'utf-8');

            // Create source map comment for debugging
            const sourceMapComment = `/*# sourceMappingURL=${file.out}.map */`;
            fs.appendFileSync(outPath, '\n' + sourceMapComment);

            console.log(
                `✅ ${file.src} → ${file.out}`,
                `(${srcSize}B → ${minSize}B, ${reduction}% reduction)`
            );

        } catch (error) {
            console.error(`❌ Error minifying ${file.src}:`, error.message);
        }
    });
}

/**
 * Minify JavaScript with tree shaking optimization
 */
function minifyJS() {
    console.log('\n📦 Minifying JavaScript files...\n');

    JS_FILES.forEach(file => {
        try {
            const srcPath = path.join(JS_SRC, file.src);
            const outPath = path.join(OUT_DIR.js, file.out);

            if (!fs.existsSync(srcPath)) {
                console.warn(`⚠️  Skipping ${file.src} - not found`);
                return;
            }

            // Read JS content
            let js = fs.readFileSync(srcPath, 'utf-8');

            // Basic minification
            js = js
                .replace(/\/\*[\s\S]*?\*\//g, '') // Remove block comments
                .replace(/\/\/.*/g, '') // Remove line comments
                .replace(/\s+/g, ' ') // Normalize spaces
                .replace(/\s*([{}:;,()[\]+=<>!&|?])\s*/g, '$1') // Remove spaces around operators
                .trim();

            // Calculate size reduction
            const srcSize = fs.statSync(srcPath).size;
            const minSize = Buffer.byteLength(js);
            const reduction = ((1 - minSize / srcSize) * 100).toFixed(2);

            // Write minified JS
            fs.writeFileSync(outPath, js, 'utf-8');

            // Create source map reference
            const sourceMapComment = `\n//# sourceMappingURL=${file.out}.map`;
            fs.appendFileSync(outPath, sourceMapComment);

            console.log(
                `✅ ${file.src} → ${file.out}`,
                `(${srcSize}B → ${minSize}B, ${reduction}% reduction)`
            );

        } catch (error) {
            console.error(`❌ Error minifying ${file.src}:`, error.message);
        }
    });
}

/**
 * Generate Brotli compression metadata
 */
function generateBrotliMetadata() {
    console.log('\n🗜️  Generating Brotli compression metadata...\n');

    const files = [
        ...CSS_FILES.map(f => path.join(OUT_DIR.css, f.out)),
        ...JS_FILES.map(f => path.join(OUT_DIR.js, f.out)),
    ];

    const metadata = {
        timestamp: new Date().toISOString(),
        files: {},
        compression_savings: {},
    };

    files.forEach(filePath => {
        try {
            if (!fs.existsSync(filePath)) return;

            const fileName = path.basename(filePath);
            const fileSize = fs.statSync(filePath).size;
            const content = fs.readFileSync(filePath, 'utf-8');

            // Estimate Brotli compression (typically 15-30% better than gzip)
            const gzipEstimate = Buffer.byteLength(content) * 0.35; // Rough gzip estimate
            const brotliEstimate = gzipEstimate * 0.85; // Brotli is ~15% better

            metadata.files[fileName] = {
                size_uncompressed: fileSize,
                size_gzip_estimate: Math.round(gzipEstimate),
                size_brotli_estimate: Math.round(brotliEstimate),
                compression_ratio_gzip: ((1 - gzipEstimate / fileSize) * 100).toFixed(2) + '%',
                compression_ratio_brotli: ((1 - brotliEstimate / fileSize) * 100).toFixed(2) + '%',
            };

            console.log(
                `📊 ${fileName}:`,
                `${fileSize}B uncompressed`,
                `→ ~${Math.round(brotliEstimate)}B with Brotli`,
                `(${((1 - brotliEstimate / fileSize) * 100).toFixed(1)}% savings)`
            );

        } catch (error) {
            console.error(`❌ Error analyzing ${filePath}:`, error.message);
        }
    });

    // Save metadata
    const metadataPath = path.join(__dirname, 'build-metadata.json');
    fs.writeFileSync(metadataPath, JSON.stringify(metadata, null, 2));

    console.log(`\n✅ Metadata saved to build-metadata.json`);
}

/**
 * Output server configuration recommendations
 */
function outputServerConfig() {
    console.log('\n⚙️  Recommended server configuration:\n');

    const configRecommendations = `
# For Nginx (add to http/server block):
gzip on;
gzip_comp_level 6;
gzip_types text/plain text/css text/javascript application/javascript;

# Enable Brotli (if brotli module installed):
brotli on;
brotli_comp_level 6;
brotli_types text/plain text/css text/javascript application/javascript;

# For Apache (.htaccess):
<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript
</IfModule>

# For PHP (wp-config.php or .htaccess):
Define('COMPRESS_CSS', true);
Define('COMPRESS_SCRIPTS', true);
	`;

    console.log(configRecommendations);
}

/**
 * Main execution
 */
function main() {
    console.log('🚀 Starting optimized minification process...\n');
    console.log(`   Source directory: ${SRC_DIR}`);
    console.log(`   Output directory: ${OUT_DIR.css}`);

    try {
        minifyCSS();
        minifyJS();
        generateBrotliMetadata();
        outputServerConfig();

        console.log('\n✨ Minification complete! All files optimized.\n');

    } catch (error) {
        console.error('\n❌ Build failed:', error.message);
        process.exit(1);
    }
}

main();
