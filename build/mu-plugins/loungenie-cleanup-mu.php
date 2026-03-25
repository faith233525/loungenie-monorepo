<?php
/**
 * LounGenie One-time Cleanup MU-Plugin
 *
 * Usage:
 * 1) Replace the TOKEN constant below with a strong random token.
 * 2) Paste this file into /wp-content/mu-plugins/ (Plugin File Editor or FTP).
 * 3) Visit: https://your-site.example/?t=run-cleanup&k=THE_TOKEN  (or staging URL)
 *
 * What it does:
 * - Scans the WordPress installation root (ABSPATH) for files with extensions
 *   .py, .csv, and .html (root-level files) and moves them into /backups/.
 * - Attempts to truncate/clear common error_log files (root and wp-content/error_log).
 * - Writes a JSON report into /backups/loungenie_cleanup_report.json and a
 *   marker file /backups/loungenie_cleanup_done.txt to prevent re-runs.
 * - After a successful run it renames itself to add a .disabled suffix to avoid
 *   accidental re-execution.
 *
 * IMPORTANT: Replace the TOKEN value below before visiting the URL.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function() {
    $TOKEN = 'REPLACE_ME_WITH_STRONG_TOKEN'; // <<< REPLACE THIS BEFORE RUNNING

    // Only trigger via explicit query param
    if ( empty( $_GET['t'] ) || 'run-cleanup' !== $_GET['t'] ) {
        return;
    }

    // Token check
    if ( empty( $_GET['k'] ) || $_GET['k'] !== $TOKEN ) {
        status_header( 403 );
        wp_die( 'Forbidden: invalid or missing token.' );
    }

    // Root and backups
    $root    = realpath( ABSPATH );
    $backups = $root . '/backups';

    if ( ! file_exists( $backups ) ) {
        @mkdir( $backups, 0755, true );
    }

    $marker = $backups . '/loungenie_cleanup_done.txt';
    if ( file_exists( $marker ) ) {
        echo "Cleanup already run. Marker: " . basename( $marker );
        exit;
    }

    $moved  = array();
    $errors = array();

    // Scan root-level files only for safety
    try {
        $it = new DirectoryIterator( $root );
    } catch ( Exception $e ) {
        wp_die( 'Failed to open root directory: ' . $e->getMessage() );
    }

    $exts = array( 'py', 'csv', 'html' );

    foreach ( $it as $fileinfo ) {
        if ( $fileinfo->isDot() || $fileinfo->isDir() ) {
            continue;
        }
        $fname = $fileinfo->getFilename();
        $ext   = strtolower( pathinfo( $fname, PATHINFO_EXTENSION ) );

        if ( in_array( $ext, $exts, true ) ) {
            $src = $fileinfo->getPathname();
            $dst = $backups . '/' . $fname;
            // attempt rename/move
            if ( @rename( $src, $dst ) ) {
                $moved[] = $fname;
            } else {
                $errors[] = "failed to move $fname";
            }
        }
    }

    // Truncate common error_log files if writable
    $logs    = array( $root . '/error_log', WP_CONTENT_DIR . '/error_log' );
    $cleared = array();

    foreach ( $logs as $log ) {
        if ( file_exists( $log ) ) {
            if ( is_writable( $log ) ) {
                $f = @fopen( $log, 'w' );
                if ( $f ) {
                    fwrite( $f, '' );
                    fclose( $f );
                    $cleared[] = $log;
                } else {
                    $errors[] = "cannot truncate $log";
                }
            } else {
                $errors[] = "not writable: $log";
            }
        }
    }

    // Write report and marker
    $report = array(
        'timestamp'    => gmdate( 'c' ),
        'moved'        => $moved,
        'cleared_logs' => $cleared,
        'errors'       => $errors,
    );

    @file_put_contents( $backups . '/loungenie_cleanup_report.json', wp_json_encode( $report, JSON_PRETTY_PRINT ) );
    @file_put_contents( $marker, "cleanup run at " . gmdate( 'c' ) . "\n" );

    echo "Cleanup complete\n";
    if ( $moved ) {
        echo "Moved: " . implode( ', ', $moved ) . "\n";
    } else {
        echo "Moved: none\n";
    }
    if ( $cleared ) {
        echo "Cleared logs: " . implode( ', ', $cleared ) . "\n";
    }
    if ( $errors ) {
        echo "Errors: " . implode( '; ', $errors ) . "\n";
    }

    // Try to disable this file after successful run to avoid accidental re-runs
    $self     = __FILE__;
    $disabled = $self . '.disabled';
    @rename( $self, $disabled );

    exit;
} );
