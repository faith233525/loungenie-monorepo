<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class RouterTest extends WPTestCase
{
    public function test_handle_portal_route_redirects_when_not_logged_in(): void
    {
        // Simulate /portal route
        when('get_query_var')->alias(function($key){
            return $key === 'lgp_portal' ? true : null;
        });
        when('is_user_logged_in')->justReturn(false);
        when('home_url')->alias(function($path){ return $path; });
        expect('wp_login_url')->once()->with('/portal')->andReturn('login_url');
        expect('wp_redirect')->once()->with('login_url')->andReturnUsing(function(){
            throw new Exception('redirected');
        });

        require_once __DIR__ . '/../includes/class-lgp-router.php';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redirected');
        LGP_Router::handle_portal_route();
    }

    public function test_access_denied_for_non_portal_roles(): void
    {
        when('get_query_var')->alias(function($key){
            return $key === 'lgp_portal' ? true : null;
        });
        when('is_user_logged_in')->justReturn(true);
        when('wp_get_current_user')->justReturn((object)['roles' => ['subscriber']]);
        expect('__')->zeroOrMoreTimes()->andReturnUsing(function($s){ return $s; });
        expect('esc_html__')->zeroOrMoreTimes()->andReturnUsing(function($s){ return $s; });
        expect('wp_die')->once()->andReturnUsing(function(){
            throw new Exception('access denied');
        });

        require_once __DIR__ . '/../includes/class-lgp-router.php';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('access denied');
        LGP_Router::handle_portal_route();
    }
}
