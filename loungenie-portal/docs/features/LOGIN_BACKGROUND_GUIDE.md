# Login Page Background Image Guide

## How to Add a Background Image in WordPress

### Option 1: Custom CSS in WordPress (Recommended)

Add this to **Appearance > Customize > Additional CSS**:

```css
/* Login page background image */
body.login {
    background-image: url('https://your-site.com/wp-content/uploads/your-image.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

/* Overlay for better card contrast */
body.login::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    z-index: 0;
}

body.login .login-container {
    position: relative;
    z-index: 1;
}
```

### Option 2: Add to Login Handler Class

In `includes/class-lgp-login-handler.php`, add this method:

```php
/**
 * Enqueue custom login styles
 */
public function enqueue_login_styles() {
    if ( is_page( 'login' ) || isset( $_GET['lgp_login'] ) ) {
        $background_image = get_option( 'lgp_login_background_image', '' );
        
        if ( $background_image ) {
            wp_add_inline_style( 'lgp-login-styles', "
                body {
                    background-image: url('{$background_image}');
                    background-size: cover;
                    background-position: center;
                    background-attachment: fixed;
                }
                body::before {
                    background: rgba(15, 23, 42, 0.3);
                }
            " );
        }
    }
}
```

Then register in `init()`:
```php
add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_login_styles' ] );
```

### Option 3: Settings Page with Media Uploader

Add a settings page for admins to upload background:

```php
/**
 * Register login settings
 */
public function register_settings() {
    register_setting( 'lgp_login_settings', 'lgp_login_background_image' );
    
    add_settings_section(
        'lgp_login_appearance',
        'Login Page Appearance',
        null,
        'lgp_login_settings'
    );
    
    add_settings_field(
        'lgp_login_background_image',
        'Background Image',
        [ $this, 'background_image_field' ],
        'lgp_login_settings',
        'lgp_login_appearance'
    );
}

/**
 * Background image upload field
 */
public function background_image_field() {
    $image = get_option( 'lgp_login_background_image', '' );
    ?>
    <input type="text" id="lgp_login_bg_image" name="lgp_login_background_image" 
           value="<?php echo esc_url( $image ); ?>" style="width: 400px;" />
    <button type="button" class="button" id="lgp_upload_bg_image">Upload Image</button>
    
    <script>
    jQuery(document).ready(function($) {
        $('#lgp_upload_bg_image').click(function(e) {
            e.preventDefault();
            var image = wp.media({ 
                title: 'Select Login Background',
                multiple: false
            }).open().on('select', function() {
                var uploaded = image.state().get('selection').first();
                $('#lgp_login_bg_image').val(uploaded.toJSON().url);
            });
        });
    });
    </script>
    <?php
}
```

## Best Background Images for Login Pages

### Industry Standards:
1. **Property/Real Estate Photos** (Best for LounGenie)
   - Modern apartment buildings
   - Pool/amenity areas
   - Property management scenes
   - Professional real estate photography

2. **Abstract/Geometric**
   - Subtle patterns
   - Gradient meshes
   - Clean geometric shapes

3. **Blurred/Soft Focus**
   - City skylines (blurred)
   - Office environments (soft focus)
   - Modern architecture (defocused)

### Image Specifications:
- **Resolution**: 1920x1080 or higher
- **Format**: JPG (optimized) or WebP
- **Size**: Under 500KB (use compression)
- **Aspect Ratio**: 16:9 or wider
- **Style**: Professional, not distracting
- **Brightness**: Medium to dark (so white card stands out)

### Free Stock Photo Resources:
- **Unsplash**: https://unsplash.com/s/photos/apartment-building
- **Pexels**: https://pexels.com/search/property-management/
- **Pixabay**: https://pixabay.com/images/search/real-estate/

### Search Keywords:
- "modern apartment building"
- "property management"
- "luxury apartments exterior"
- "real estate professional"
- "building facade modern"
- "amenity pool area"

## Implementation Example

```php
// In loungenie-portal.php or class-lgp-login-handler.php

add_action( 'wp_head', function() {
    if ( is_page( 'login' ) ) {
        $bg_image = get_option( 'lgp_login_background_image', '' );
        if ( $bg_image ) {
            ?>
            <style>
                body {
                    background-image: url('<?php echo esc_url( $bg_image ); ?>');
                    background-size: cover;
                    background-position: center;
                    background-attachment: fixed;
                }
                body::before {
                    background: rgba(15, 23, 42, 0.35) !important;
                }
                .login-container {
                    background: rgba(255, 255, 255, 0.95) !important;
                    backdrop-filter: blur(30px) !important;
                }
            </style>
            <?php
        }
    }
});
```

## Testing Your Background

1. Upload a test image to WordPress Media Library
2. Get the image URL
3. Add CSS with that URL
4. Check on different screen sizes
5. Verify card is still readable
6. Adjust overlay opacity if needed

## Tips for Best Results

✅ **Do:**
- Use high-quality, professional images
- Keep backgrounds subtle (avoid busy patterns)
- Test on mobile devices
- Use image compression (TinyPNG, Squoosh)
- Add a semi-transparent overlay for contrast
- Consider brand colors

❌ **Don't:**
- Use low-resolution images (pixelated)
- Choose distracting or busy backgrounds
- Forget mobile responsiveness
- Skip image optimization
- Use bright backgrounds (hard to read)
- Violate image licensing

## Current Login Design Features

The login page is ready for background images with:
- ✅ Glassmorphism effect (backdrop blur)
- ✅ Subtle dot pattern (can be overridden)
- ✅ Overlay support
- ✅ Mobile responsive
- ✅ Industry-standard light theme
- ✅ Professional spacing and shadows
