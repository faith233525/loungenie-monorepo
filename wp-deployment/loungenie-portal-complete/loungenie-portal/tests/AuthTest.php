<?php

use function Brain\Monkey\Functions\when;

final class AuthTest extends WPTestCase
{
    public function test_redirect_after_login_for_portal_roles(): void
    {
        $user = (object)['roles' => ['lgp_partner']];
        when('home_url')->alias(function($path){ return $path; });

        require_once __DIR__ . '/../includes/class-lgp-auth.php';
        $result = LGP_Auth::redirect_after_login('/wp-admin', '', $user);
        $this->assertSame('/portal', $result);
    }

    public function test_redirect_after_login_for_non_portal_roles(): void
    {
        $user = (object)['roles' => ['subscriber']];
        require_once __DIR__ . '/../includes/class-lgp-auth.php';
        $result = LGP_Auth::redirect_after_login('/wp-admin', '', $user);
        $this->assertSame('/wp-admin', $result);
    }

    public function test_is_support_and_is_partner(): void
    {
        when('is_user_logged_in')->justReturn(true);
        when('wp_get_current_user')->justReturn((object)['roles' => ['lgp_support']]);
        require_once __DIR__ . '/../includes/class-lgp-auth.php';
        $this->assertTrue(LGP_Auth::is_support());
        $this->assertFalse(LGP_Auth::is_partner());

        // Switch role
        when('wp_get_current_user')->justReturn((object)['roles' => ['lgp_partner']]);
        $this->assertFalse(LGP_Auth::is_support());
        $this->assertTrue(LGP_Auth::is_partner());
    }
}
