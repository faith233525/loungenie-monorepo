<?php
/**
 * Shared Hosting Constraints & Guardrails
 * Explicit rules for maintaining stability on shared WordPress hosting
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared Hosting Architecture Rules
 *
 * These are NOT guidelines. They are HARD CONSTRAINTS for this deployment.
 * Any code violating these rules should be rejected in code review.
 */
class LGP_Shared_Hosting_Rules {

	/**
	 * RULE #1: Request-Bound Logic Only
	 *
	 * Allowed:
	 * - Page loads (template_redirect)
	 * - REST requests
	 * - WP-Cron (daily, hourly)
	 *
	 * NOT Allowed:
	 * ❌ WebSockets
	 * ❌ Persistent connections
	 * ❌ Polling loops
	 * ❌ Background listeners
	 * ❌ Async message queues
	 *
	 * Rationale: Shared hosting terminates connections after a few seconds.
	 *           Long-running processes get killed or cause timeout penalties.
	 */
	const RULE_1_REQUEST_BOUND = true;

	/**
	 * RULE #2: REST Endpoint Performance
	 *
	 * Hard limits:
	 * - Response time: <300ms (p95)
	 * - Always paginate (max 100 items/page)
	 * - Max join depth: 2 tables
	 * - Never SELECT * (specify columns)
	 *
	 * Test on shared hosting before merge.
	 */
	const RULE_2_REST_PERFORMANCE = array(
		'max_response_time_ms' => 300,
		'max_items_per_page'   => 100,
		'max_join_depth'       => 2,
		'require_pagination'   => true,
		'require_index_on_fk'  => true,
	);

	/**
	 * RULE #3: WP-Cron Only
	 *
	 * Allowed frequencies:
	 * - Hourly (lgp_hourly)
	 * - Daily (lgp_daily)
	 * - Weekly (lgp_weekly)
	 *
	 * NOT Allowed:
	 * ❌ More than once per minute
	 * ❌ Real-time gateway ingestion
	 * ❌ Streaming telemetry
	 * ❌ Frequent heartbeat checks
	 *
	 * Rationale: Shared hosting throttles frequent cron tasks.
	 *           Prevents resource contention.
	 */
	const RULE_3_WPCRON_ONLY = true;

	/**
	 * RULE #4: Asset Discipline
	 *
	 * Required:
	 * - Conditional enqueue (only on /portal/*)
	 * - Bundle per-view (portal.js, tickets-view.js, etc.)
	 * - No global wp_enqueue_scripts
	 * - Minified CSS/JS
	 *
	 * Prevents:
	 * ❌ Bloated page loads on other sections
	 * ❌ Memory exhaustion
	 * ❌ CSS conflicts with theme
	 */
	const RULE_4_ASSET_DISCIPLINE = array(
		'conditional_enqueue'   => true,
		'per_view_bundles'      => true,
		'minified'              => true,
		'cdn_only_for_external' => true,
	);

	/**
	 * RULE #5: File Upload Hard Limits
	 *
	 * Enforced:
	 * - Max file size: 10MB
	 * - MIME whitelist only
	 * - Store with randomized names
	 * - Never use user input for filenames
	 * - Delete after 90 days if unused
	 *
	 * Prevents:
	 * ❌ Disk exhaustion
	 * ❌ MIME type exploits
	 * ❌ Directory traversal
	 */
	const RULE_5_FILE_UPLOADS = array(
		'max_size_bytes'       => 10485760, // 10MB
		'max_files_per_ticket' => 5,
		'allowed_mimes'        => array(
			'image/jpeg',
			'image/png',
			'application/pdf',
			'text/plain',
		),
		'retention_days'       => 90,
	);

	/**
	 * RULE #6: Conservative CSP
	 *
	 * Required header:
	 * default-src 'self'
	 * script-src 'self' unpkg.com cdn.jsdelivr.net
	 * style-src 'self'
	 * img-src 'self' data: https:
	 *
	 * NOT Allowed:
	 * ❌ unsafe-inline (scripts or styles)
	 * ❌ * domains
	 * ❌ Inline event handlers
	 */
	const RULE_6_CSP_CONSERVATIVE = true;

	/**
	 * RULE #7: Soft Rate Limiting
	 *
	 * Applied to:
	 * - Ticket creation (5 per hour per user)
	 * - Attachments (10 per hour per user)
	 * - REST list endpoints (100 req/min per IP)
	 *
	 * Implementation: WordPress transients (soft, not enforced)
	 * Prevents accidental abuse and bot damage.
	 */
	const RULE_7_RATE_LIMITING = array(
		'ticket_create_per_hour' => 5,
		'attachment_per_hour'    => 10,
		'list_endpoint_per_min'  => 100,
	);

	/**
	 * RULE #8: Database Constraints
	 *
	 * Assume on shared hosting:
	 * - Small buffer pool (no query cache)
	 * - Limited concurrent connections
	 * - 60-second query timeout
	 *
	 * Required:
	 * - Every foreign key indexed
	 * - Avoid JSON blobs for hot paths
	 * - Use transients for computed values
	 * - Paginate all list queries
	 */
	const RULE_8_DATABASE_REALISM = array(
		'query_timeout_seconds'       => 60,
		'require_fk_indexes'          => true,
		'normalize_over_json'         => true,
		'use_transients_for_computed' => true,
	);

	/**
	 * RULE #9: What NOT to Add (Violations)
	 *
	 * These belong on v2 infrastructure only:
	 *
	 * ❌ Real-time gateway listeners
	 * ❌ WebSockets
	 * ❌ Server-side AI inference
	 * ❌ Continuous telemetry ingestion
	 * ❌ Heavy analytics (Mixpanel, Segment, etc.)
	 * ❌ Background job queues (Bull, RQ, Celery)
	 * ❌ Message brokers (RabbitMQ, Redis Pub/Sub)
	 * ❌ Video streaming
	 * ❌ Full-text search engines (Elasticsearch)
	 * ❌ MapReduce / batch processing
	 *
	 * Instead:
	 * ✅ Move to managed cloud service (v2.0)
	 * ✅ Use third-party SaaS (HubSpot, Stripe, etc.)
	 * ✅ Batch jobs at WP-Cron
	 */
	const RULE_9_DO_NOT_ADD = array(
		'real_time_listeners',
		'websockets',
		'server_side_ai',
		'continuous_telemetry',
		'heavy_analytics',
		'background_queues',
		'message_brokers',
		'video_streaming',
		'full_text_search',
		'batch_processing',
	);

	/**
	 * Validate incoming code against rules
	 *
	 * Used in code review process to catch violations early.
	 */
	public static function lint_for_violations( $code ) {
		$violations = array();

		// Check for WebSocket usage
		if ( preg_match( '/websocket|ws:\/\//i', $code ) ) {
			$violations[] = array(
				'rule'    => 1,
				'message' => 'WebSockets not allowed on shared hosting',
			);
		}

		// Check for async keywords (non-standard in PHP)
		if ( preg_match( '/Promise|async|await/i', $code ) ) {
			$violations[] = array(
				'rule'    => 1,
				'message' => 'Async patterns require background infrastructure',
			);
		}

		// Check for message queue usage
		if ( preg_match( '/queue|celery|bull|rq|rabbitmq|pubsub/i', $code ) ) {
			$violations[] = array(
				'rule'    => 9,
				'message' => 'Message brokers belong on v2 infrastructure',
			);
		}

		// Check for SELECT * (performance violation)
		if ( preg_match( '/SELECT\s+\*|SELECT\s+t\.\*/i', $code ) ) {
			$violations[] = array(
				'rule'    => 2,
				'message' => 'Use explicit column selection, never SELECT *',
			);
		}

		return $violations;
	}

	/**
	 * Log a rule violation for auditing
	 *
	 * @param int    $rule   Rule number
	 * @param string $reason Reason for violation
	 */
	public static function log_violation( $rule, $reason ) {
		error_log(
			wp_json_encode(
				array(
					'context'   => 'LGP_Shared_Hosting_Rules',
					'violation' => "RULE #{$rule}",
					'reason'    => $reason,
					'timestamp' => current_time( 'mysql' ),
				)
			)
		);
	}

	/**
	 * Print enforcement policy for documentation
	 */
	public static function get_enforcement_policy() {
		return <<<'EOF'
# LounGenie Portal: Shared Hosting Enforcement Policy

## Code Review Checklist

Before merging ANY code:

- [ ] Does this run during page load or REST request only?
- [ ] Is REST response time <300ms (measured)?
- [ ] Are all foreign keys indexed?
- [ ] Does it use WP-Cron only (not real-time)?
- [ ] Are assets conditionally enqueued (/portal/* only)?
- [ ] Does it respect file upload limits?
- [ ] CSP-compliant (no unsafe-inline)?
- [ ] Rate limited (if applicable)?
- [ ] No WebSockets, background queues, or persistent connections?

## Penalties for Violations

1. **Rule 1-3 (Critical):** PR rejected, escalation required
2. **Rule 4-7 (Major):** PR blocked until remediated
3. **Rule 8 (Performance):** Performance review required
4. **Rule 9 (Scope):** Deferred to v2.0 roadmap

## Exception Process

To request exception:

1. File issue: "Architecture Exception Request"
2. Justify: Why not possible on shared hosting?
3. Document: Migration path to v2.0?
4. Approve: CTO + DevOps sign-off required

---

**Last Updated:** December 18, 2025
**Policy Owner:** Architecture Team
**Review Cadence:** Quarterly
EOF;
	}
}
