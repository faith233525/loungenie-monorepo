<?php
/**
 * Reusable Card Component
 *
 * Eliminates repeated card HTML structures throughout templates
 * Usage: LGP_Component::card($args)
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render card component
 *
 * @param array $args {
 *     Card configuration arguments
 *
 *     @type string $title       Card title
 *     @type string $subtitle    Optional subtitle
 *     @type string $content     Card content (HTML allowed)
 *     @type string $footer      Optional footer content
 *     @type string $icon        Optional icon (emoji or HTML)
 *     @type string $class       Additional CSS classes
 *     @type string $link        Optional card link URL
 *     @type string $badge       Optional badge text
 *     @type string $badge_type  Badge type: 'brand', 'success', 'warning', 'danger'
 *     @type bool   $hover       Enable hover effect (default: true)
 *     @type bool   $shadow      Enable shadow (default: true)
 * }
 */
function lgp_render_card( $args = array() ) {
	$defaults = array(
		'title'      => '',
		'subtitle'   => '',
		'content'    => '',
		'footer'     => '',
		'icon'       => '',
		'class'      => '',
		'link'       => '',
		'badge'      => '',
		'badge_type' => 'brand',
		'hover'      => true,
		'shadow'     => true,
	);

	$args = wp_parse_args( $args, $defaults );

	$card_classes = array( 'lgp-card' );
	if ( $args['hover'] ) {
		$card_classes[] = 'lgp-card-hover';
	}
	if ( $args['shadow'] ) {
		$card_classes[] = 'lgp-shadow-sm';
	}
	if ( $args['class'] ) {
		$card_classes[] = esc_attr( $args['class'] );
	}

	$card_tag   = $args['link'] ? 'a' : 'div';
	$card_attrs = $args['link'] ? ' href="' . esc_url( $args['link'] ) . '"' : '';

	?>
	<<?php echo $card_tag; ?> class="<?php echo implode( ' ', $card_classes ); ?>"<?php echo $card_attrs; ?>>
		<?php if ( $args['icon'] || $args['badge'] ) : ?>
		<div class="lgp-card-header lgp-flex lgp-items-center lgp-justify-between lgp-mb-4">
			<?php if ( $args['icon'] ) : ?>
			<div class="lgp-card-icon lgp-text-2xl"><?php echo $args['icon']; ?></div>
			<?php endif; ?>
			<?php if ( $args['badge'] ) : ?>
			<span class="lgp-badge lgp-badge-<?php echo esc_attr( $args['badge_type'] ); ?>">
				<?php echo esc_html( $args['badge'] ); ?>
			</span>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $args['title'] ) : ?>
		<h3 class="lgp-card-title lgp-text-lg lgp-font-semibold lgp-text-primary lgp-mb-2">
			<?php echo esc_html( $args['title'] ); ?>
		</h3>
		<?php endif; ?>

		<?php if ( $args['subtitle'] ) : ?>
		<p class="lgp-card-subtitle lgp-text-sm lgp-text-secondary lgp-mb-4">
			<?php echo esc_html( $args['subtitle'] ); ?>
		</p>
		<?php endif; ?>

		<?php if ( $args['content'] ) : ?>
		<div class="lgp-card-content lgp-text-secondary">
			<?php echo wp_kses_post( $args['content'] ); ?>
		</div>
		<?php endif; ?>

		<?php if ( $args['footer'] ) : ?>
		<div class="lgp-card-footer lgp-mt-4 lgp-pt-4 lgp-border-t lgp-border-border">
			<?php echo wp_kses_post( $args['footer'] ); ?>
		</div>
		<?php endif; ?>
	</<?php echo $card_tag; ?>>
	<?php
}

/**
 * Render stats card (metrics display)
 *
 * @param array $args {
 *     @type string $label     Stat label
 *     @type string $value     Stat value
 *     @type string $change    Optional change indicator (e.g., "+12%")
 *     @type string $icon      Optional icon
 *     @type string $color     Color theme: 'brand', 'success', 'warning', 'danger'
 * }
 */
function lgp_render_stat_card( $args = array() ) {
	$defaults = array(
		'label'  => '',
		'value'  => '',
		'change' => '',
		'icon'   => '',
		'color'  => 'brand',
	);

	$args = wp_parse_args( $args, $defaults );

	$color_map = array(
		'brand'   => 'var(--lgp-brand-teal)',
		'success' => 'var(--lgp-success)',
		'warning' => 'var(--lgp-warning)',
		'danger'  => 'var(--lgp-danger)',
	);

	$color = isset( $color_map[ $args['color'] ] ) ? $color_map[ $args['color'] ] : $color_map['brand'];

	?>
	<div class="lgp-card lgp-card-stat">
		<?php if ( $args['icon'] ) : ?>
		<div class="lgp-stat-icon lgp-text-2xl lgp-mb-2" style="--lgp-stat-color: <?php echo esc_attr( $color ); ?>;">
			<?php echo $args['icon']; ?>
		</div>
		<?php endif; ?>

		<div class="lgp-stat-value lgp-text-3xl lgp-font-bold lgp-mb-1" style="--lgp-stat-color: <?php echo esc_attr( $color ); ?>;">
			<?php echo esc_html( $args['value'] ); ?>
		</div>

		<div class="lgp-stat-label lgp-text-sm lgp-text-secondary lgp-mb-2">
			<?php echo esc_html( $args['label'] ); ?>
		</div>

		<?php if ( $args['change'] ) : ?>
		<div class="lgp-stat-change lgp-text-xs lgp-font-medium" 
			style="--lgp-stat-change-color: <?php echo strpos( $args['change'], '-' ) === 0 ? 'var(--lgp-danger)' : 'var(--lgp-success)'; ?>;">
			<?php echo esc_html( $args['change'] ); ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render button
 *
 * @param array $args {
 *     @type string $text      Button text
 *     @type string $url       Button URL
 *     @type string $type      Button type: 'primary', 'secondary', 'outline'
 *     @type string $size      Button size: 'sm', 'base', 'lg'
 *     @type string $icon      Optional icon
 *     @type string $class     Additional classes
 *     @type array  $attrs     Additional attributes
 * }
 */
function lgp_render_button( $args = array() ) {
	$defaults = array(
		'text'  => '',
		'url'   => '#',
		'type'  => 'primary',
		'size'  => 'base',
		'icon'  => '',
		'class' => '',
		'attrs' => array(),
	);

	$args = wp_parse_args( $args, $defaults );

	$button_classes = array( 'lgp-btn', 'lgp-btn-' . $args['type'] );
	if ( $args['size'] !== 'base' ) {
		$button_classes[] = 'lgp-btn-' . $args['size'];
	}
	if ( $args['class'] ) {
		$button_classes[] = esc_attr( $args['class'] );
	}

	$attrs_string = '';
	foreach ( $args['attrs'] as $key => $value ) {
		$attrs_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
	}

	?>
	<a href="<?php echo esc_url( $args['url'] ); ?>" 
		class="<?php echo implode( ' ', $button_classes ); ?>"
		<?php echo $attrs_string; ?>>
		<?php if ( $args['icon'] ) : ?>
		<span class="lgp-btn-icon"><?php echo $args['icon']; ?></span>
		<?php endif; ?>
		<span class="lgp-btn-text"><?php echo esc_html( $args['text'] ); ?></span>
	</a>
	<?php
}

/**
 * Render badge
 *
 * @param string $text Badge text
 * @param string $type Badge type: 'brand', 'success', 'warning', 'danger'
 * @param string $class Additional classes
 */
function lgp_render_badge( $text, $type = 'brand', $class = '' ) {
	$badge_classes = array( 'lgp-badge', 'lgp-badge-' . esc_attr( $type ) );
	if ( $class ) {
		$badge_classes[] = esc_attr( $class );
	}

	?>
	<span class="<?php echo implode( ' ', $badge_classes ); ?>">
		<?php echo esc_html( $text ); ?>
	</span>
	<?php
}

/**
 * Render empty state
 *
 * @param array $args {
 *     @type string $icon      Icon (emoji or HTML)
 *     @type string $title     Empty state title
 *     @type string $message   Empty state message
 *     @type string $action    Optional action button HTML
 * }
 */
function lgp_render_empty_state( $args = array() ) {
	$defaults = array(
		'icon'    => '📭',
		'title'   => 'No items found',
		'message' => 'There are no items to display.',
		'action'  => '',
	);

	$args = wp_parse_args( $args, $defaults );

	?>
	<div class="lgp-empty-state lgp-text-center lgp-py-12">
		<div class="lgp-empty-icon lgp-text-6xl lgp-mb-4 lgp-opacity-50">
			<?php echo $args['icon']; ?>
		</div>
		<h3 class="lgp-empty-title lgp-text-xl lgp-font-semibold lgp-text-primary lgp-mb-2">
			<?php echo esc_html( $args['title'] ); ?>
		</h3>
		<p class="lgp-empty-message lgp-text-secondary lgp-mb-6">
			<?php echo esc_html( $args['message'] ); ?>
		</p>
		<?php if ( $args['action'] ) : ?>
		<div class="lgp-empty-action">
			<?php echo wp_kses_post( $args['action'] ); ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render loading spinner
 *
 * @param string $text Optional loading text
 * @param string $size Size: 'sm', 'base', 'lg'
 */
function lgp_render_loading( $text = '', $size = 'base' ) {
	$sizes = array(
		'sm'   => 'lgp-spinner-sm',
		'base' => '',
		'lg'   => 'lgp-spinner-lg',
	);

	$size_class = isset( $sizes[ $size ] ) ? $sizes[ $size ] : '';

	?>
	<div class="lgp-loading-container lgp-flex lgp-items-center lgp-justify-center lgp-gap-3 lgp-py-8">
		<span class="lgp-spinner <?php echo esc_attr( $size_class ); ?>"></span>
		<?php if ( $text ) : ?>
		<span class="lgp-loading-text lgp-text-secondary"><?php echo esc_html( $text ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render table wrapper (for responsive tables)
 *
 * @param string $content Table HTML content
 * @param bool   $responsive Enable responsive mode
 */
function lgp_render_table( $content, $responsive = true ) {
	$wrapper_class = $responsive ? 'lgp-table-wrapper lgp-table-responsive-wrapper' : 'lgp-table-wrapper';
	?>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>">
		<?php echo wp_kses_post( $content ); ?>
	</div>
	<?php
}
