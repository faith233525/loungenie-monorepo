<?php
/**
 * Training Videos View
 * Support: Upload and manage videos
 * Partners: View assigned videos
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_support = LGP_Auth::is_support();
$categories = LGP_Training_Video::get_categories();
?>

<div class="lgp-training-container">
    <div class="lgp-page-header">
        <h1><?php esc_html_e( 'Training Videos', 'loungenie-portal' ); ?></h1>
        <?php if ( $is_support ) : ?>
            <button id="lgp-add-video-btn" class="lgp-button lgp-button-primary">
                <?php esc_html_e( '+ Add Video', 'loungenie-portal' ); ?>
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Filters -->
    <div class="lgp-training-filters">
        <input 
            type="search" 
            id="lgp-training-search" 
            placeholder="<?php esc_attr_e( 'Search videos...', 'loungenie-portal' ); ?>"
            class="lgp-search-input"
        />
        
        <select id="lgp-category-filter" class="lgp-select-input">
            <option value=""><?php esc_html_e( 'All Categories', 'loungenie-portal' ); ?></option>
            <?php foreach ( $categories as $category ) : ?>
                <option value="<?php echo esc_attr( $category ); ?>">
                    <?php echo esc_html( ucfirst( $category ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Video Grid -->
    <div id="lgp-training-grid" class="lgp-training-grid">
        <div class="lgp-loading"><?php esc_html_e( 'Loading videos...', 'loungenie-portal' ); ?></div>
    </div>
    
    <!-- No Videos Message -->
    <div id="lgp-no-videos" class="lgp-empty-state" style="display:none;">
        <p><?php esc_html_e( 'No training videos available.', 'loungenie-portal' ); ?></p>
        <?php if ( $is_support ) : ?>
            <p><?php esc_html_e( 'Click "Add Video" to create your first training video.', 'loungenie-portal' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Video Modal (Support Only) -->
<?php if ( $is_support ) : ?>
<div id="lgp-video-modal" class="lgp-modal-overlay hidden">
    <div class="lgp-modal">
        <div class="lgp-modal-header">
            <h3 id="lgp-modal-title"><?php esc_html_e( 'Add Training Video', 'loungenie-portal' ); ?></h3>
            <button class="lgp-modal-close">&times;</button>
        </div>
        <div class="lgp-modal-body">
            <form id="lgp-video-form">
                <input type="hidden" id="lgp-video-id" value="" />
                
                <div class="lgp-form-group">
                    <label for="lgp-video-title"><?php esc_html_e( 'Title', 'loungenie-portal' ); ?> *</label>
                    <input type="text" id="lgp-video-title" required class="lgp-input" />
                </div>
                
                <div class="lgp-form-group">
                    <label for="lgp-video-description"><?php esc_html_e( 'Description', 'loungenie-portal' ); ?></label>
                    <textarea id="lgp-video-description" rows="4" class="lgp-textarea"></textarea>
                </div>
                
                <div class="lgp-form-group">
                    <label for="lgp-video-url"><?php esc_html_e( 'Video URL', 'loungenie-portal' ); ?> *</label>
                    <input type="url" id="lgp-video-url" required class="lgp-input" placeholder="https://youtube.com/watch?v=..." />
                    <small class="lgp-help-text">
                        <?php esc_html_e( 'YouTube, Vimeo, or direct video URL supported', 'loungenie-portal' ); ?>
                    </small>
                </div>
                
                <div class="lgp-form-group">
                    <label for="lgp-video-category"><?php esc_html_e( 'Category', 'loungenie-portal' ); ?></label>
                    <select id="lgp-video-category" class="lgp-select-input">
                        <option value="general"><?php esc_html_e( 'General', 'loungenie-portal' ); ?></option>
                        <option value="installation"><?php esc_html_e( 'Installation', 'loungenie-portal' ); ?></option>
                        <option value="troubleshooting"><?php esc_html_e( 'Troubleshooting', 'loungenie-portal' ); ?></option>
                        <option value="maintenance"><?php esc_html_e( 'Maintenance', 'loungenie-portal' ); ?></option>
                        <option value="product-overview"><?php esc_html_e( 'Product Overview', 'loungenie-portal' ); ?></option>
                    </select>
                </div>
                
                <div class="lgp-form-group">
                    <label for="lgp-video-duration"><?php esc_html_e( 'Duration (seconds)', 'loungenie-portal' ); ?></label>
                    <input type="number" id="lgp-video-duration" class="lgp-input" placeholder="300" />
                </div>
                
                <div class="lgp-form-group">
                    <label><?php esc_html_e( 'Target Companies (optional)', 'loungenie-portal' ); ?></label>
                    <div id="lgp-company-selector" class="lgp-checkbox-group">
                        <label>
                            <input type="checkbox" id="lgp-all-companies" checked /> 
                            <?php esc_html_e( 'All Companies', 'loungenie-portal' ); ?>
                        </label>
                        <div id="lgp-company-list" style="display:none;"></div>
                    </div>
                    <small class="lgp-help-text">
                        <?php esc_html_e( 'Leave "All Companies" checked to make video available to everyone', 'loungenie-portal' ); ?>
                    </small>
                </div>
                
                <div class="lgp-modal-footer">
                    <button type="button" class="lgp-button lgp-button-secondary lgp-modal-close">
                        <?php esc_html_e( 'Cancel', 'loungenie-portal' ); ?>
                    </button>
                    <button type="submit" class="lgp-button lgp-button-primary">
                        <?php esc_html_e( 'Save Video', 'loungenie-portal' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Video Player Modal (All Users) -->
<div id="lgp-player-modal" class="lgp-modal-overlay hidden">
    <div class="lgp-modal lgp-modal-large">
        <div class="lgp-modal-header">
            <h3 id="lgp-player-title"></h3>
            <button class="lgp-modal-close">&times;</button>
        </div>
        <div class="lgp-modal-body">
            <div id="lgp-video-player" class="lgp-video-player"></div>
            <div id="lgp-video-description" class="lgp-video-description"></div>
        </div>
    </div>
</div>

<style>
.lgp-training-container {
    padding: var(--space-lg);
}

.lgp-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-xl);
}

.lgp-training-filters {
    display: flex;
    gap: var(--space-md);
    margin-bottom: var(--space-lg);
}

.lgp-search-input,
.lgp-select-input {
    padding: var(--space-sm) var(--space-md);
    border: 1px solid var(--soft);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
}

.lgp-search-input {
    flex: 1;
    min-width: 300px;
}

.lgp-training-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-lg);
}

.lgp-video-card {
    background: var(--white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.lgp-video-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.lgp-video-thumbnail {
    width: 100%;
    height: 180px;
    background: var(--background);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    cursor: pointer;
    position: relative;
}

.lgp-video-thumbnail::after {
    content: '▶';
    position: absolute;
    color: var(--white);
    font-size: 64px;
    opacity: 0.8;
}

.lgp-video-card-body {
    padding: var(--space-md);
}

.lgp-video-card-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    margin-bottom: var(--space-sm);
    color: var(--dark);
}

.lgp-video-card-meta {
    display: flex;
    gap: var(--space-sm);
    margin-bottom: var(--space-sm);
    font-size: var(--font-size-sm);
    color: var(--neutral);
}

.lgp-video-card-description {
    font-size: var(--font-size-sm);
    color: var(--neutral);
    margin-bottom: var(--space-md);
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.lgp-video-card-actions {
    display: flex;
    gap: var(--space-sm);
}

.lgp-modal-large {
    max-width: 900px;
}

.lgp-video-player {
    width: 100%;
    min-height: 400px;
    background: var(--dark);
    border-radius: var(--radius-sm);
    margin-bottom: var(--space-md);
}

.lgp-video-player iframe {
    width: 100%;
    height: 500px;
    border: none;
    border-radius: var(--radius-sm);
}

.lgp-video-description {
    font-size: var(--font-size-base);
    color: var(--neutral);
    line-height: 1.6;
}

.lgp-form-group {
    margin-bottom: var(--space-md);
}

.lgp-form-group label {
    display: block;
    margin-bottom: var(--space-xs);
    font-weight: 600;
    color: var(--dark);
}

.lgp-input,
.lgp-textarea {
    width: 100%;
    padding: var(--space-sm);
    border: 1px solid var(--soft);
    border-radius: var(--radius-sm);
    font-family: var(--font-family);
}

.lgp-textarea {
    resize: vertical;
}

.lgp-help-text {
    display: block;
    margin-top: var(--space-xs);
    font-size: 12px;
    color: var(--neutral);
}

.lgp-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.lgp-empty-state {
    text-align: center;
    padding: var(--space-2xl);
    color: var(--neutral);
}

.lgp-loading {
    text-align: center;
    padding: var(--space-2xl);
    color: var(--neutral);
}
</style>
