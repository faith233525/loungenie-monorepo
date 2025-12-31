<?php
/**
 * LounGenie Portal - Email Deduplication
 * Prevents duplicate ticket creation from same email processed via multiple paths
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Deduplication
{

    const DEDUP_TABLE     = 'lgp_email_dedup';
    const DEDUP_HASH_META = '_lgp_email_hash';
    const HASH_WINDOW     = 3600; // 1 hour window for deduplication

    /**
     * Initialize deduplication system
     */
    public static function init()
    {
        // Create dedup table on init if needed
        self::ensure_table_exists();
    }

    /**
     * Ensure dedup table exists
     */
    public static function ensure_table_exists()
    {
        global $wpdb;

        $table           = $wpdb->prefix . self::DEDUP_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email_hash varchar(64) NOT NULL UNIQUE,
			ticket_id bigint(20) UNSIGNED,
			company_id bigint(20) UNSIGNED,
			source varchar(50) NOT NULL DEFAULT 'hook',
			processed_at datetime DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime,
			PRIMARY KEY (id),
			UNIQUE KEY email_hash (email_hash),
			KEY ticket_id (ticket_id),
			KEY company_id (company_id),
			KEY expires_at (expires_at)
		) $charset_collate;";

        include_once LGP_PLUGIN_DIR . 'includes/lgp-upgrade-shim.php';
        dbDelta($sql);
    }

    /**
     * Generate deduplication hash from email metadata
     *
     * @param  string     $sender_email Email sender
     * @param  string     $subject      Email subject
     * @param  int|string $date         Email date (timestamp or string)
     * @return string SHA256 hash
     */
    public static function generate_hash( $sender_email, $subject, $date )
    {
        // Normalize inputs
        // @phpstan-ignore-next-line sanitize_email and sanitize_text_field are WordPress core
        $sender_email = strtolower(sanitize_email($sender_email));
        $subject      = strtolower(trim(sanitize_text_field($subject)));

        // Convert date to timestamp if string
        if (is_string($date) ) {
            $timestamp = strtotime($date);
        } else {
            $timestamp = (int) $date;
        }

        // Round timestamp to nearest minute to allow slight variations
        $normalized_time = ( $timestamp / 60 ) * 60;

        // Create hash from normalized data
        $data = $sender_email . '|' . $subject . '|' . $normalized_time;

        return hash('sha256', $data);
    }

    /**
     * Check if email has been processed
     *
     * @param  string $email_hash Email hash
     * @return array|false Dedup record or false
     */
    public static function get_dedup_record( $email_hash )
    {
        global $wpdb;

        $table = $wpdb->prefix . self::DEDUP_TABLE;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE email_hash = %s AND (expires_at IS NULL OR expires_at > NOW())",
                $email_hash
            )
        );
    }

    /**
     * Register email as processed
     *
     * @param  string $email_hash Email hash
     * @param  int    $ticket_id  Ticket ID created
     * @param  int    $company_id Company ID
     * @param  string $source     Source path (hook|pop3|smtp)
     * @return int|false Insert ID or false
     */
    public static function register_processed_email( $email_hash, $ticket_id, $company_id, $source = 'hook' )
    {
        global $wpdb;

        $table = $wpdb->prefix . self::DEDUP_TABLE;

        // Calculate expiration
        $expires_at = date('Y-m-d H:i:s', time() + self::HASH_WINDOW);

        return $wpdb->insert(
            $table,
            array(
            'email_hash' => $email_hash,
            'ticket_id'  => $ticket_id,
            'company_id' => $company_id,
            'source'     => $source,
            'expires_at' => $expires_at,
            ),
            array( '%s', '%d', '%d', '%s', '%s' )
        );
    }

    /**
     * Mark email as duplicate and link to original
     *
     * @param  string $email_hash         Email hash
     * @param  int    $original_ticket_id Original ticket ID
     * @return int|false Update result or false
     */
    public static function mark_as_duplicate( $email_hash, $original_ticket_id )
    {
        global $wpdb;

        $table = $wpdb->prefix . self::DEDUP_TABLE;

        return $wpdb->update(
            $table,
            array(
            'ticket_id' => $original_ticket_id,
            ),
            array(
            'email_hash' => $email_hash,
            ),
            array( '%d' ),
            array( '%s' )
        );
    }

    /**
     * Clean expired dedup records
     *
     * @return int|false Rows deleted
     */
    public static function cleanup_expired()
    {
        global $wpdb;

        $table = $wpdb->prefix . self::DEDUP_TABLE;

        return $wpdb->query(
            "DELETE FROM $table WHERE expires_at < NOW()"
        );
    }

    /**
     * Get dedup statistics for monitoring
     *
     * @return array Statistics
     */
    public static function get_stats()
    {
        global $wpdb;

        $table = $wpdb->prefix . self::DEDUP_TABLE;

        return array(
        'total_deduplicated' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table"),
        'active_hashes'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE expires_at > NOW()"),
        'by_source'          => $wpdb->get_results(
            "SELECT source, COUNT(*) as count FROM $table GROUP BY source",
            ARRAY_A
        ),
        );
    }
}

// Initialize on every page load
LGP_Deduplication::init();
