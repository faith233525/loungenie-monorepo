<?php

use function Brain\Monkey\Functions\when;

final class MicrosoftSSOTest extends WPTestCase
{
    public function test_get_authorization_url_builds_expected_params(): void
    {
        when('get_option')->alias(function($key){
            switch ($key) {
                case 'lgp_m365_client_id': return 'CLIENT_ID';
                case 'lgp_m365_tenant_id': return 'TENANT_ID';
                default: return '';
            }
        });
        when('admin_url')->justReturn('https://example.com/wp-admin/options-general.php?page=lgp-m365-settings&oauth_callback=1');
        when('wp_create_nonce')->justReturn('NONCE');

        require_once __DIR__ . '/../includes/class-lgp-microsoft-sso.php';
        $url = LGP_Microsoft_SSO::get_authorization_url();

        $this->assertStringContainsString('client_id=CLIENT_ID', $url);
        $this->assertStringContainsString('TENANT_ID/oauth2/v2.0/authorize?', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=NONCE', $url);
        $this->assertStringContainsString('scope=openid+profile+email+User.Read', $url);
    }

    public function test_refresh_access_token_success(): void
    {
        when('get_option')->alias(function($key){
            $map = [
                'lgp_m365_refresh_token' => 'REFRESH',
                'lgp_m365_client_id' => 'CLIENT_ID',
                'lgp_m365_client_secret' => 'CLIENT_SECRET',
                'lgp_m365_tenant_id' => 'TENANT_ID',
            ];
            return $map[$key] ?? '';
        });
        when('wp_remote_post')->justReturn([
            'body' => json_encode([
                'access_token' => 'NEW_ACCESS',
                'refresh_token' => 'NEW_REFRESH',
                'expires_in' => 3600,
            ])
        ]);
        when('is_wp_error')->justReturn(false);
        when('wp_remote_retrieve_body')->alias(function($response){ return $response['body']; });
        when('update_option')->justReturn(true);

        require_once __DIR__ . '/../includes/class-lgp-microsoft-sso.php';
        $ok = LGP_Microsoft_SSO::refresh_access_token();
        $this->assertTrue($ok);
    }
}
