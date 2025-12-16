<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

// Minimal WP_Error shim for tests (no wp-phpunit)
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code; private $message;
        public function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; }
        public function get_error_message() { return $this->message; }
    }
}

final class SSOErrorTest extends WPTestCase
{
    private function setCallbackGet(array $overrides): void
    {
        $_GET = array_merge([
            'page' => 'lgp-m365-settings',
            'oauth_callback' => '1',
            'code' => 'dummy-code',
            'state' => 'dummy-state',
        ], $overrides);
    }

    private function commonStubs(): void
    {
        // i18n helpers
        when('__')->alias(fn($s)=>$s);
        when('esc_html__')->alias(fn($s)=>$s);

        // WP option + url helpers
        when('admin_url')->alias(fn($p='')=>$p);
        when('update_option')->justReturn(true);
        when('get_option')->alias(function($k,$d=null){ return $d ?? ''; });

        // Sanitization
        when('sanitize_text_field')->alias(fn($v)=>$v);

        // HTTP helpers
        when('wp_remote_retrieve_body')->alias(function($resp){ return is_array($resp)&&isset($resp['body'])?$resp['body']:''; });

        // Die/redirect controls
        expect('wp_die')->zeroOrMoreTimes()->andReturnUsing(function($msg=''){ throw new Exception((string)$msg ?: 'wp_die'); });
        expect('wp_safe_redirect')->zeroOrMoreTimes()->andReturnUsing(function($url){ throw new Exception('redirect:'.$url); });
    }

    public function test_invalid_nonce_triggers_wp_die(): void
    {
        $this->commonStubs();
        $this->setCallbackGet(['state' => 'bad-state']);

        // Nonce verification fails
        when('wp_verify_nonce')->alias(fn($state,$key)=>false);

        require_once __DIR__ . '/../includes/class-lgp-microsoft-sso.php';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid state parameter');
        LGP_Microsoft_SSO::handle_oauth_callback();
    }

    public function test_token_exchange_failure_wp_error(): void
    {
        $this->commonStubs();
        $this->setCallbackGet([]);

        when('wp_verify_nonce')->alias(fn($state,$key)=>true);
        // Simulate network error from token endpoint
        when('wp_remote_post')->alias(function($url,$args){ return new WP_Error('http_error','Network fail'); });

        require_once __DIR__ . '/../includes/class-lgp-microsoft-sso.php';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Network fail');
        LGP_Microsoft_SSO::handle_oauth_callback();
    }

    public function test_missing_access_token_leads_to_error_flow(): void
    {
        $this->commonStubs();
        $this->setCallbackGet([]);

        when('wp_verify_nonce')->alias(fn($state,$key)=>true);
        // Token response missing/empty access token
        when('wp_remote_post')->alias(function($url,$args){
            return [ 'body' => json_encode(['access_token' => '', 'expires_in' => 3600]) ];
        });
        // Graph call returns error because token unusable
        when('wp_remote_get')->alias(function($url,$args){
            return [ 'body' => json_encode(['error' => ['message' => 'Missing access token']]) ];
        });

        require_once __DIR__ . '/../includes/class-lgp-microsoft-sso.php';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing access token');
        LGP_Microsoft_SSO::handle_oauth_callback();
    }

    public function test_graph_api_error_bubbles_to_wp_die(): void
    {
        $this->commonStubs();
        $this->setCallbackGet([]);

        when('wp_verify_nonce')->alias(fn($state,$key)=>true);
        // Successful token exchange
        when('wp_remote_post')->alias(function($url,$args){
            return [ 'body' => json_encode(['access_token' => 'good-token', 'refresh_token' => 'rr', 'expires_in' => 3600]) ];
        });
        // Graph returns structured error
        when('wp_remote_get')->alias(function($url,$args){
            return [ 'body' => json_encode(['error' => ['message' => 'Graph down']]) ];
        });

        require_once __DIR__ . '/../includes/class-lgp-microsoft-sso.php';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Graph down');
        LGP_Microsoft_SSO::handle_oauth_callback();
    }
}
