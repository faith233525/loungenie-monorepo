<?php

/**
 * Company Color Aggregates Display Component
 *
 * Displays company-level unit color distribution as aggregated counts.
 * NO individual unit IDs are shown - only total counts per color.
 *
 * Usage:
 * <?php
 * require_once 'path/to/component-company-colors.php';
 * lgp_render_company_colors( $company_id );
 * ?>
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Render company color aggregates
 *
 * @param int   $company_id Company ID
 * @param array $args       Optional configuration
 *                          - show_total: bool
 *                          Display total units
 *                          count - show_labels:
 *                          bool Display color
 *                          names - layout: string
 *                          'inline' | 'stacked' -
 *                          size: string 'small' |
 *                          'medium' | 'large'
 */
function lgp_render_company_colors( $company_id, $args = array() )
{
    // Default arguments
    $defaults = array(
    'show_total'  => true,
    'show_labels' => true,
    'layout'      => 'inline',
    'size'        => 'medium',
    );

    $args = wp_parse_args($args, $defaults);

    // Get color aggregates using utility class
    if (! class_exists('LGP_Company_Colors') ) {
        return;
    }

    $colors      = LGP_Company_Colors::get_company_colors($company_id);
    $total_units = LGP_Company_Colors::get_company_unit_count($company_id);

    // If no colors, show message
    if (empty($colors) || $total_units === 0 ) {
        echo '<div class="lgp-no-colors">';
        esc_html_e('No units configured', 'loungenie-portal');
        echo '</div>';
        return;
    }

    // Container classes
    $container_classes = array(
    'lgp-company-colors',
    'lgp-layout-' . esc_attr($args['layout']),
    'lgp-size-' . esc_attr($args['size']),
    );

    ?>
    <div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>">
    <?php if ($args['show_total'] ) : ?>
            <div class="lgp-total-units">
                <strong><?php echo absint($total_units); ?></strong>
        <?php echo esc_html(_n('Unit', 'Units', $total_units, 'loungenie-portal')); ?>
            </div>
    <?php endif; ?>

        <div class="lgp-color-badges">
    <?php foreach ( $colors as $color => $count ) : ?>
                <div class="lgp-color-badge" data-color="<?php echo esc_attr($color); ?>">
                    <span class="lgp-color-icon lgp-color-<?php echo esc_attr(strtolower($color)); ?>"
                        title="<?php echo esc_attr(ucfirst($color)); ?>">
                    </span>
                    <span class="lgp-color-count"><?php echo absint($count); ?></span>
        <?php if ($args['show_labels'] ) : ?>
                        <span class="lgp-color-label"><?php echo esc_html(ucfirst($color)); ?></span>
        <?php endif; ?>
                </div>
    <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Get formatted color aggregates for JSON response
 *
 * @param  int $company_id Company ID
 * @return array Formatted color data
 */
function lgp_get_company_colors_json( $company_id )
{
    if (! class_exists('LGP_Company_Colors') ) {
        return array(
        'total_units' => 0,
        'colors'      => array(),
        );
    }

    $colors      = LGP_Company_Colors::get_company_colors($company_id);
    $total_units = LGP_Company_Colors::get_company_unit_count($company_id);

    // Format for API response
    $formatted_colors = array();
    foreach ( $colors as $color => $count ) {
        $formatted_colors[] = array(
        'color' => $color,
        'count' => (int) $count,
        'hex'   => LGP_Company_Colors::get_color_hex($color),
        );
    }

    return array(
    'total_units' => (int) $total_units,
    'colors'      => $formatted_colors,
    );
}

/**
 * Render compact color summary (icon-only display)
 *
 * @param int $company_id Company ID
 * @param int $max_colors Maximum colors to show (0 = all)
 */
function lgp_render_company_colors_compact( $company_id, $max_colors = 3 )
{
    if (! class_exists('LGP_Company_Colors') ) {
        return;
    }

    $colors = LGP_Company_Colors::get_company_colors($company_id);

    if (empty($colors) ) {
        return;
    }

    // Sort by count (descending) and limit
    arsort($colors);
    if ($max_colors > 0 ) {
        $colors = array_slice($colors, 0, $max_colors, true);
    }

    $remaining = count($colors) - $max_colors;

    ?>
    <div class="lgp-company-colors-compact">
    <?php foreach ( $colors as $color => $count ) : ?>
            <span class="lgp-color-icon lgp-color-<?php echo esc_attr(strtolower($color)); ?>"
                title="<?php echo esc_attr(ucfirst($color) . ': ' . $count); ?>">
            </span>
    <?php endforeach; ?>
    <?php if ($remaining > 0 ) : ?>
            <span class="lgp-colors-more" title="<?php echo esc_attr(sprintf(__('+%d more', 'loungenie-portal'), $remaining)); ?>">
                +<?php echo absint($remaining); ?>
            </span>
    <?php endif; ?>
    </div>
    <?php
}

/**
 * Render color distribution chart (percentage bars)
 *
 * @param int $company_id Company ID
 */
function lgp_render_company_colors_chart( $company_id )
{
    if (! class_exists('LGP_Company_Colors') ) {
        return;
    }

    $colors      = LGP_Company_Colors::get_company_colors($company_id);
    $total_units = LGP_Company_Colors::get_company_unit_count($company_id);

    if (empty($colors) || $total_units === 0 ) {
        echo '<p class="lgp-no-data">' . esc_html__('No color data available', 'loungenie-portal') . '</p>';
        return;
    }

    // Sort by count (descending)
    arsort($colors);

    ?>
    <div class="lgp-color-chart">
    <?php foreach ( $colors as $color => $count ) : ?>
        <?php
        $percentage = round(( $count / $total_units ) * 100, 1);
        $hex_color  = LGP_Company_Colors::get_color_hex($color);
        ?>
            <div class="lgp-color-row">
                <div class="lgp-color-info">
                    <span class="lgp-color-icon lgp-color-<?php echo esc_attr(strtolower($color)); ?>"></span>
                    <span class="lgp-color-name"><?php echo esc_html(ucfirst($color)); ?></span>
                </div>
                <div class="lgp-color-bar-container">
                    <div class="lgp-color-bar"
                        style="width: <?php echo esc_attr($percentage); ?>%; background-color: <?php echo esc_attr($hex_color); ?>;">
                    </div>
                </div>
                <div class="lgp-color-stats">
                    <span class="lgp-color-count"><?php echo absint($count); ?></span>
                    <span class="lgp-color-percent">(<?php echo esc_html($percentage); ?>%)</span>
                </div>
            </div>
    <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Example Usage:
 *
 * // Basic display with all options
 * lgp_render_company_colors( 123 );
 *
 * // Compact icon-only display
 * lgp_render_company_colors_compact( 123, 3 );
 *
 * // Chart with percentages
 * lgp_render_company_colors_chart( 123 );
 *
 * // Custom configuration
 * lgp_render_company_colors( 123, array(
 *     'show_total'  => false,
 *     'show_labels' => false,
 *     'layout'      => 'stacked',
 *     'size'        => 'small',
 * ) );
 *
 * // For API/JSON responses
 * $data = lgp_get_company_colors_json( 123 );
 * wp_send_json_success( $data );
 */
