<?php

/**
 * Help and Guides View
 * Support: Upload and manage videos
 * Partners: View assigned videos
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

$is_support = LGP_Auth::is_support();
$categories = LGP_Training_Video::get_categories();
?>

<div class="lgp-help-guides-container">
	<div class="lgp-page-header">
		<h1><?php esc_html_e('Knowledge Guides', 'loungenie-portal'); ?></h1>
		<?php if ($is_support) : ?>
			<button id="lgp-add-video-btn" class="lgp-btn lgp-btn-primary">
				<?php esc_html_e('+ Add Guide', 'loungenie-portal'); ?>
			</button>
		<?php endif; ?>
	</div>

	<!-- Filters -->
	<div class="lgp-help-guides-filters">
		<input
			type="search"
			id="lgp-help-guides-search"
			placeholder="<?php esc_attr_e('Search videos...', 'loungenie-portal'); ?>"
			class="lgp-search-input" />

		<select id="lgp-category-filter" class="lgp-select-input">
			<option value=""><?php esc_html_e('All Categories', 'loungenie-portal'); ?></option>
			<?php foreach ($categories as $category) : ?>
				<option value="<?php echo esc_attr($category); ?>">
					<?php echo esc_html(ucfirst($category)); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<!-- Video Grid -->
	<div id="lgp-help-guides-grid" class="lgp-help-guides-grid">
		<div class="lgp-loading"><?php esc_html_e('Loading videos...', 'loungenie-portal'); ?></div>
	</div>

	<!-- No Videos Message -->
	<div id="lgp-no-videos" class="lgp-empty-state lgp-hidden">
		<p><?php esc_html_e('No knowledge guides available.', 'loungenie-portal'); ?></p>
		<?php if ($is_support) : ?>
			<p><?php esc_html_e('Click "Add Guide" to create your first knowledge guide.', 'loungenie-portal'); ?></p>
		<?php endif; ?>
	</div>
</div>

<!-- Add/Edit Video Modal (Support Only) -->
<?php if ($is_support) : ?>
	<div id="lgp-video-modal" class="lgp-modal-overlay hidden">
		<div class="lgp-modal">
			<div class="lgp-modal-header">
				<h3 id="lgp-modal-title"><?php esc_html_e('Add Knowledge Guide', 'loungenie-portal'); ?></h3>
				<button class="lgp-modal-close">&times;</button>
			</div>
			<div class="lgp-modal-body">
				<form id="lgp-video-form">
					<input type="hidden" id="lgp-video-id" value="" />

					<div class="lgp-form-group">
						<label for="lgp-video-title"><?php esc_html_e('Title', 'loungenie-portal'); ?> *</label>
						<input type="text" id="lgp-video-title" required class="lgp-input" />
					</div>

					<div class="lgp-form-group">
						<label for="lgp-video-description"><?php esc_html_e('Description', 'loungenie-portal'); ?></label>
						<textarea id="lgp-video-description" rows="4" class="lgp-textarea"></textarea>
					</div>

					<div class="lgp-form-group">
						<label for="lgp-video-url"><?php esc_html_e('Video URL', 'loungenie-portal'); ?> *</label>
						<input type="url" id="lgp-video-url" required class="lgp-input" placeholder="https://youtube.com/watch?v=..." />
						<small class="lgp-help-text">
							<?php esc_html_e('YouTube, Vimeo, or direct video URL supported', 'loungenie-portal'); ?>
						</small>
					</div>

					<div class="lgp-form-group">
						<label for="lgp-video-category"><?php esc_html_e('Category', 'loungenie-portal'); ?></label>
						<select id="lgp-video-category" class="lgp-select-input">
							<option value="general"><?php esc_html_e('General', 'loungenie-portal'); ?></option>
							<option value="installation"><?php esc_html_e('Installation', 'loungenie-portal'); ?></option>
							<option value="troubleshooting"><?php esc_html_e('Troubleshooting', 'loungenie-portal'); ?></option>
							<option value="maintenance"><?php esc_html_e('Maintenance', 'loungenie-portal'); ?></option>
							<option value="product-overview"><?php esc_html_e('Product Overview', 'loungenie-portal'); ?></option>
						</select>
					</div>

					<div class="lgp-form-group">
						<label for="lgp-video-duration"><?php esc_html_e('Duration (seconds)', 'loungenie-portal'); ?></label>
						<input type="number" id="lgp-video-duration" class="lgp-input" placeholder="300" />
					</div>

					<div class="lgp-form-group">
						<label><?php esc_html_e('Target Companies (optional)', 'loungenie-portal'); ?></label>
						<div id="lgp-company-selector" class="lgp-checkbox-group">
							<label>
								<input type="checkbox" id="lgp-all-companies" checked />
								<?php esc_html_e('All Companies', 'loungenie-portal'); ?>
							</label>
							<div id="lgp-company-list" class="lgp-hidden"></div>
						</div>
						<small class="lgp-help-text">
							<?php esc_html_e('Leave "All Companies" checked to make video available to everyone', 'loungenie-portal'); ?>
						</small>
					</div>

					<div class="lgp-modal-footer">
						<button type="button" class="lgp-button lgp-button-secondary lgp-modal-close">
							<?php esc_html_e('Cancel', 'loungenie-portal'); ?>
						</button>
						<button type="submit" class="lgp-button lgp-button-primary">
							<?php esc_html_e('Save Video', 'loungenie-portal'); ?>
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