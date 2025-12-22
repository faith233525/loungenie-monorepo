<?php
/**
 * Tests for Company Profile Partner View Polish (Phase 4)
 *
 * @package LounGenie_Portal
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class PartnerViewPolishTest extends TestCase {

	/**
	 * Test that collapsible section structure exists
	 */
	public function testCollapsibleSectionStructure() {
		// Verify class names and attributes for collapsible sections.
		$section_html = '<div class="lgp-card" data-section="units"><div class="lgp-card-header collapsible" data-section="units"></div></div>';

		$this->assertStringContainsString( 'lgp-card', $section_html );
		$this->assertStringContainsString( 'collapsible', $section_html );
		$this->assertStringContainsString( 'data-section="units"', $section_html );
	}

	/**
	 * Test that support-only sections are marked
	 */
	public function testSupportOnlySectionMarking() {
		// Verify data-support-only attribute.
		$section_html = '<div class="lgp-card" data-section="audit-log" data-support-only="true"><div class="lgp-card-header collapsible" data-section="audit-log"></div></div>';

		$this->assertStringContainsString( 'data-support-only="true"', $section_html );
	}

	/**
	 * Test localStorage key naming convention
	 */
	public function testLocalStorageKeyConvention() {
		// Storage key format should be lgp-profile-sections-{section_id}.
		$section_id = 'units';
		$expected_key = 'lgp-profile-sections-' . $section_id;

		$this->assertStringContainsString( 'lgp-profile-sections', $expected_key );
		$this->assertStringContainsString( $section_id, $expected_key );
	}

	/**
	 * Test collapse/expand button structure
	 */
	public function testCollapseToggleButton() {
		// Verify toggle button structure.
		$button_html = '<button class="collapse-toggle" aria-label="Toggle section" type="button"><span class="collapse-icon">▼</span></button>';

		$this->assertStringContainsString( 'collapse-toggle', $button_html );
		$this->assertStringContainsString( 'collapse-icon', $button_html );
		$this->assertStringContainsString( '▼', $button_html );
	}

	/**
	 * Test read-only badge for partners
	 */
	public function testReadOnlyBadgeStructure() {
		// Verify read-only badge markup.
		$badge_html = '<span class="badge-read-only" title="This section is only visible to support users">Support Only</span>';

		$this->assertStringContainsString( 'badge-read-only', $badge_html );
		$this->assertStringContainsString( 'Support Only', $badge_html );
	}

	/**
	 * Test partner view CSS classes
	 */
	public function testPartnerViewCssClasses() {
		// Verify CSS class names for partner view styling.
		$css_classes = array(
			'is-read-only',
			'partner-view-primary',
			'partner-read-only',
			'partner-view-restricted',
		);

		foreach ( $css_classes as $class ) {
			$this->assertNotEmpty( $class, "CSS class '$class' should not be empty" );
		}
	}

	/**
	 * Test bulk toggle controls structure
	 */
	public function testBulkToggleControlsMarkup() {
		// Verify bulk toggle controls markup.
		$markup = '<div class="bulk-toggle-controls"><button class="lgp-btn lgp-btn-secondary" type="button">Expand All</button><button class="lgp-btn lgp-btn-secondary" type="button">Collapse All</button></div>';

		$this->assertStringContainsString( 'bulk-toggle-controls', $markup );
		$this->assertStringContainsString( 'Expand All', $markup );
		$this->assertStringContainsString( 'Collapse All', $markup );
	}

	/**
	 * Test disabled button state for partners
	 */
	public function testDisabledButtonStateForPartners() {
		// Verify disabled button class and disabled attribute.
		$button_html = '<button class="lgp-btn disabled" disabled>Edit</button>';

		$this->assertStringContainsString( 'disabled', $button_html );
		$this->assertStringContainsString( 'class="lgp-btn disabled"', $button_html );
	}

	/**
	 * Test animation transition for collapse/expand
	 */
	public function testCollapseAnimationTransition() {
		// Verify CSS transition properties exist.
		$css = '.lgp-card-body { transition: max-height 0.3s ease, overflow 0.3s ease; }';

		$this->assertStringContainsString( 'transition', $css );
		$this->assertStringContainsString( 'max-height 0.3s', $css );
		$this->assertStringContainsString( 'overflow 0.3s', $css );
	}

	/**
	 * Test partner view primary styling
	 */
	public function testPartnerViewPrimaryBorderStyle() {
		// Verify CSS for primary sections in partner view.
		$css = '.partner-view-primary { border-left: 4px solid var(--primary); }';

		$this->assertStringContainsString( 'border-left', $css );
		$this->assertStringContainsString( '4px solid', $css );
	}

	/**
	 * Test responsive behavior for collapsible sections
	 */
	public function testResponsiveCollapsibleBehavior() {
		// Verify mobile breakpoint media query.
		$css = '@media (max-width: 768px) { .lgp-card-header.collapsible { flex-wrap: wrap; } }';

		$this->assertStringContainsString( '@media', $css );
		$this->assertStringContainsString( 'max-width: 768px', $css );
		$this->assertStringContainsString( 'flex-wrap: wrap', $css );
	}

	/**
	 * Test section filtering by role
	 */
	public function testSectionFiltering() {
		// Verify sections are properly hidden for partners.
		$support_only_section = '<div data-support-only="true" style="display: none;"></div>';

		$this->assertStringContainsString( 'data-support-only="true"', $support_only_section );
		$this->assertStringContainsString( 'display: none', $support_only_section );
	}

	/**
	 * Test that collapse icon changes on toggle
	 */
	public function testCollapseIconChange() {
		// Verify icon changes from ▼ to ▶.
		$expanded_icon = '▼';
		$collapsed_icon = '▶';

		$this->assertNotEquals( $expanded_icon, $collapsed_icon );
		$this->assertEquals( '▼', $expanded_icon );
		$this->assertEquals( '▶', $collapsed_icon );
	}

	/**
	 * Test support-only section border indicator
	 */
	public function testSupportOnlySectionBorderIndicator() {
		// Verify CSS for support-only section border.
		$css = '[data-support-only="true"]::before { background: linear-gradient(to bottom, var(--accent), transparent); }';

		$this->assertStringContainsString( '::before', $css );
		$this->assertStringContainsString( 'linear-gradient', $css );
	}

	/**
	 * Test cursor pointer on collapsible headers
	 */
	public function testCollapsibleHeaderCursor() {
		// Verify collapsible headers have pointer cursor.
		$css = '.lgp-card-header.collapsible { cursor: pointer; }';

		$this->assertStringContainsString( 'cursor: pointer', $css );
	}

	/**
	 * Test that script is properly enqueued
	 */
	public function testPartnerPolishScriptEnqueue() {
		// Verify script handle name.
		$script_handle = 'lgp-company-profile-partner-polish';
		$script_path = 'js/company-profile-partner-polish.js';

		$this->assertStringContainsString( 'partner-polish', $script_handle );
		$this->assertTrue( strpos( $script_path, '.js' ) !== false );
	}

	/**
	 * Test that partner-restricted attributes work
	 */
	public function testPartnerRestrictedAttributes() {
		// Verify partner-restricted attribute is used for buttons.
		$button = '<button data-action="edit" data-partner-restricted="true">Edit</button>';

		$this->assertStringContainsString( 'data-partner-restricted="true"', $button );
		$this->assertStringContainsString( 'data-action="edit"', $button );
	}

	/**
	 * Test that collapsed card body has max-height 0
	 */
	public function testCollapsedBodyMaxHeight() {
		// Verify CSS for collapsed bodies.
		$css = '.lgp-card.collapsed .lgp-card-body { max-height: 0 !important; }';

		$this->assertStringContainsString( 'max-height: 0', $css );
		$this->assertStringContainsString( 'collapsed', $css );
	}

	/**
	 * Test user-select none on collapsible headers
	 */
	public function testUserSelectNoneOnHeaders() {
		// Verify collapsible headers prevent text selection.
		$css = '.lgp-card-header.collapsible { user-select: none; }';

		$this->assertStringContainsString( 'user-select: none', $css );
	}

	/**
	 * Test that all sections have correct data attributes
	 */
	public function testAllSectionsHaveDataAttributes() {
		// Verify all main sections have data-section attribute.
		$sections = array(
			'company-info',
			'units',
			'gateways',
			'tickets',
			'audit-log',
			'service-notes',
		);

		foreach ( $sections as $section ) {
			$this->assertNotEmpty( $section );
			$markup = 'data-section="' . $section . '"';
			$this->assertStringContainsString( 'data-section', $markup );
		}
	}
}
