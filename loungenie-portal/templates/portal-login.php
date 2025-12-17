<?php
// phpcs:ignoreFile
/**
 * Portal Login Landing
 * - Support: one-click Microsoft SSO
 * - Partner: username/password via wp-login
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// URLs
$portal_url        = home_url( '/portal' );
$support_login_url = home_url( '/support-login' );
$partner_login_url = wp_login_url( $portal_url );

// Ensure strict standards mode without invoking theme APIs
echo "<!DOCTYPE html>\n";
?>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Portal Login', 'loungenie-portal' ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
	<style>
		:root {
			--midnight-navy: #0B1222;
			--canvas: #E9F8F9; /* Soft White/Cyan */
			--ink: #222222;    /* Dark Text */
			--muted: #454F5E;  /* Slate Gray */
			--teal: #3AA6B9;   /* Primary Teal */
			--gold: #D9A441; /* Brushed Gold */
			--outlook: #0A5BD5;
			--radius: 16px;
			--shadow: 0 25px 70px rgba(15, 23, 42, 0.18);
			--border: rgba(15, 23, 42, 0.06);
		}
		* { box-sizing: border-box; }
		body {
			margin: 0;
			font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			background: var(--canvas);
			color: var(--ink);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 28px;
		}
		.card {
			max-width: 980px;
			width: 100%;
			background: #fff;
			border: none; /* No hard borders */
			border-radius: var(--radius);
			box-shadow: var(--shadow);
			display: grid;
			grid-template-columns: minmax(280px, 340px) 1fr;
			overflow: hidden;
		}
		.sidebar {
			background: linear-gradient(165deg, #0c1428 0%, #0a1024 60%, #0b1222 100%);
			color: #EEF3FF;
			padding: 36px;
			display: flex;
			flex-direction: column;
			gap: 14px;
			position: relative;
		}
		.sidebar::after {
			content: "";
			position: absolute;
			inset: 18px 18px auto auto;
			width: 110px;
			height: 110px;
			background: radial-gradient(circle, rgba(217,164,65,0.16) 0%, transparent 55%);
			pointer-events: none;
		}
		.badge {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 7px 14px;
			border-radius: 999px;
			background: linear-gradient(135deg, rgba(217, 164, 65, 0.24), rgba(217, 164, 65, 0.18));
			color: #FDF6E3;
			font-weight: 600;
			font-size: 12px;
			letter-spacing: 0.5px;
			text-transform: uppercase;
		}
		.title {
			font-size: 32px;
			font-weight: 700;
			margin: 8px 0 6px;
			color: #F8FBFF;
			line-height: 1.2;
		}
		.subtitle {
			color: rgba(238, 243, 255, 0.88);
			margin: 0;
			line-height: 1.65;
			font-size: 15px;
		}
		.sidebar-list {
			list-style: none;
			margin: 24px 0 0;
			padding: 0;
			display: grid;
			gap: 16px;
		}
		.sidebar-list li {
			display: flex;
			align-items: flex-start;
			gap: 12px;
			color: rgba(238, 243, 255, 0.9);
			font-size: 14.5px;
			line-height: 1.5;
		}
		.bullet {
			width: 6px;
			height: 6px;
			border-radius: 999px;
			background: var(--gold);
			flex-shrink: 0;
			margin-top: 6px;
		}
		.main {
			padding: 44px 48px;
			display: flex;
			flex-direction: column;
			gap: 28px;
			background: #fff;
		}
		.main h2 {
			margin: 0;
			font-size: 26px;
			font-weight: 700;
			color: var(--ink);
			letter-spacing: -0.3px;
		}
		.main p {
			margin: 0;
			color: var(--muted);
			line-height: 1.65;
			font-size: 15px;
		}
		.actions {
			display: flex;
			flex-direction: column;
			gap: 14px;
			margin-top: 8px;
		}
		.button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			padding: 15px 18px;
			border-radius: 12px;
			border: 1px solid transparent;
			font-size: 15px;
			font-weight: 600;
			cursor: pointer;
			text-decoration: none;
			transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
		}
		.button:hover { 
			transform: translateY(-2px);
		}
		.button:active { transform: translateY(0); }
		.button:focus {
			outline: 3px solid rgba(18, 181, 176, 0.2);
			outline-offset: 2px;
		}
		.button-outlook {
			background: linear-gradient(135deg, #0b62e0, var(--outlook));
			color: #F8FAFC;
			box-shadow: 0 15px 35px rgba(10, 91, 213, 0.28);
			border-color: rgba(10, 91, 213, 0.12);
		}
		.button-outlook:hover {
			box-shadow: 0 18px 42px rgba(10, 91, 213, 0.35);
		}
		.button-partner {
			background: var(--teal);
			color: #F8FBFF;
			box-shadow: 0 12px 28px rgba(58, 166, 185, 0.22);
		}
		.button-partner:hover {
			box-shadow: 0 15px 35px rgba(58, 166, 185, 0.28);
		}
		.button-ghost {
			background: #fff;
			color: var(--ink);
			border-color: rgba(15, 23, 42, 0.08);
		}
		.button-ghost:hover {
			border-color: rgba(15, 23, 42, 0.18);
			box-shadow: 0 10px 26px rgba(15, 23, 42, 0.12);
		}
		.small {
			font-size: 13.5px;
			color: var(--muted);
			line-height: 1.5;
		}
		.icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 20px;
			height: 20px;
			border-radius: 4px;
			background: #0e63e6;
			color: #fff;
			font-weight: 700;
			font-size: 12px;
			box-shadow: inset 0 -1px 0 rgba(0,0,0,0.12);
		}
		.icon-envelope {
			background: linear-gradient(145deg, #0b5edc, #0a58cc);
			clip-path: polygon(0 0, 100% 0, 100% 68%, 50% 100%, 0 68%);
		}
		@media (max-width: 880px) {
			.card { grid-template-columns: 1fr; }
			.sidebar { border-bottom: 1px solid var(--border); }
		}
	</style>
</head>
<body>
	<div class="card">
		<div class="sidebar">
			<div class="badge">Luxury Access</div>
			<h1 class="title">Welcome to the LounGenie Portal</h1>
			<p class="subtitle">Choose the sign-in that fits your role. Support runs through Microsoft 365. Partners use their portal credentials.</p>
			<ul class="sidebar-list">
				<li><span class="bullet"></span><span>Support: one-click Microsoft SSO—no extra password.</span></li>
				<li><span class="bullet"></span><span>Partners: username & password with secure redirect.</span></li>
				<li><span class="bullet"></span><span>After login, we land you in the portal automatically.</span></li>
			</ul>
		</div>
		<div class="main">
			<div>
				<h2>Select Your Sign-In Method</h2>
				<p>Choose the option that matches your role. Support team members use Microsoft 365, and Partners use their portal credentials.</p>
			</div>
			<div class="actions">
				<a class="button button-outlook" href="<?php echo esc_url( $support_login_url ); ?>">
					<span class="icon icon-envelope" aria-hidden="true"></span>
					<span>Sign in with Microsoft 365 (Support)</span>
				</a>
				<a class="button button-partner" href="<?php echo esc_url( $partner_login_url ); ?>">
					<span>Partner Login (Username & Password)</span>
				</a>
				<div class="small">Need help? Use the password reset link on the partner login screen or contact support.</div>
			</div>
		</div>
	</div>
</body>
</html>
