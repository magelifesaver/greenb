=== JWT Auth Pro ===
Contributors: tmeister
Tags: jwt, authentication, rest-api, api, token, json web token, oauth, security
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Professional JWT Authentication for WordPress REST API. Follows OAuth 2.0 standards and implements industry best practices for secure API authentication.

== Description ==

JWT Auth Pro is a WordPress plugin that provides secure JSON Web Token (JWT) authentication for the WordPress REST API. It follows OAuth 2.0 standards and implements industry best practices for secure API authentication.

Features:

* 🔐 Secure JWT Authentication
* 🔄 Token Refresh Mechanism
* 📊 Analytics Dashboard
* 🛡️ Advanced Security Features
* ⚙️ Flexible Configuration
* 🎯 Developer-Friendly
* Rate limiting
* Token revocation
* Secure headers
* CORS support
* IP-based security
* Token tracking and management

= Security Features =

* Rate limiting
* Token revocation
* Secure headers
* CORS support
* IP-based security
* Token tracking and management

= Available Endpoints =

**Generate Token**
`POST /wp-json/jwt-auth/v1/token`
Parameters:
* username - WordPress username
* password - WordPress password

**Validate Token**
`POST /wp-json/jwt-auth/v1/token/validate`
Headers:
* Authorization: Bearer <token>

**Refresh Token**
`POST /wp-json/jwt-auth/v1/token/refresh`
Parameters:
* refresh_token - The refresh token received when generating a token

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/jwt-auth-pro`
2. Activate the plugin through the WordPress plugins screen
3. Configure your settings at `Settings > JWT Auth Pro`

= Configuration Constants =

// Define your secret key (Required)
define('JWT_AUTH_SECRET_KEY', 'your-secret-key');`

== Frequently Asked Questions ==

= What are the system requirements? =

* PHP 8.1 or higher
* WordPress 5.0 or higher
* REST API enabled

= What are the default token settings? =

* JWT Expiration Time: 7 days
* Refresh Token Expiration: 30 days
* Signing Algorithm: HS256

= Can I customize token revocation? =

Yes, you can control token revocation on:
* Password change
* Role change
* Email change
* User deletion

== Changelog ==

= 0.2.1 =
* Fix: Corrected refresh token validation to check the proper expiration field, ensuring tokens can be refreshed correctly even after access token expiration
* Enhancement: Updated support contact from ticketing system to direct email for faster assistance

= 0.2.0 =
* Feature: Added token self-revocation API endpoint for enhanced security control
* Feature: Improved admin interface with modern design and better navigation
* Fix: JavaScript clients can now access rate limiting headers via CORS
* Fix: Resolved authentication issues with token validation and null pointer errors
* Enhancement: Upgraded UI components for improved user experience
* Enhancement: Added comprehensive testing infrastructure for better reliability
* Performance: Optimized development workflow with enhanced CI/CD pipeline

= 0.1.0 =
* Initial release
* Secure JWT Authentication
* Token Refresh Mechanism
* Analytics Dashboard
* Advanced Security Features
* Developer-Friendly API
* Modern Admin Interface

== Upgrade Notice ==

= 0.2.1 =
Fixed bug in refresh token validation that was incorrectly checking access token expiration.

= 0.2.0 =
Added token self-revocation endpoint, improved admin UI, fixed CORS headers for JavaScript clients, and enhanced testing infrastructure.

= 0.1.0 =
Initial release of JWT Auth Pro with comprehensive JWT authentication features.
