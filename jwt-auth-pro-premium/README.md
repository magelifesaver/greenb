# JWT Auth Pro

Professional JWT Authentication for WordPress REST API

## Description

JWT Auth Pro is a WordPress plugin that provides secure JSON Web Token (JWT) authentication for the WordPress REST API. It follows OAuth 2.0 standards and implements industry best practices for secure API authentication.

## Features

- 🔐 Secure JWT Authentication
- 🔄 Token Refresh Mechanism
- 📊 Analytics Dashboard
- 🛡️ Advanced Security Features
- ⚙️ Flexible Configuration
- 🎯 Developer-Friendly

## Requirements

- PHP 8.1 or higher
- WordPress 5.0 or higher
- REST API enabled

## Installation

1. Upload the plugin files to `/wp-content/plugins/jwt-auth-pro`
2. Activate the plugin through the WordPress plugins screen
3. Configure your settings at `Settings > JWT Auth Pro`

## Configuration

### Constants

```php
// Enable CORS support
define('JWT_AUTH_CORS_ENABLE', true);

// Define your secret key (Required)
define('JWT_AUTH_SECRET_KEY', 'your-secret-key');
```

### Available Endpoints

#### Generate Token

```
POST /wp-json/jwt-auth/v1/token
```

Parameters:
```username``` - WordPress username
```password``` - WordPress password

#### Validate Token

```
POST /wp-json/jwt-auth/v1/token/validate
```

Headers:
```Authorization: Bearer <token>```

#### Refresh Token

```
POST /wp-json/jwt-auth/v1/token/refresh
```

Parameters:
```refresh_token``` - The refresh token received when generating a token

#### Revoke Token

```
POST /wp-json/jwt-auth/v1/token/revoke
```

Headers:
```Authorization: Bearer <token>```

Revokes the current JWT token, making it immediately invalid. Useful for implementing logout functionality in client applications.

## Settings

### Token Settings

- JWT Expiration Time (default: 7 days)
- Refresh Token Expiration (default: 30 days)
- Signing Algorithm (default: HS256)

### User Settings

- Revoke tokens on password change
- Revoke tokens on role change
- Revoke tokens on email change
- Delete tokens on user deletion

### Data Management

- Analytics retention period
- IP anonymization
- Data cleanup on plugin deactivation

## Developer Documentation

### Available Filters

#### Token Generation

```php
// Modify user data before authentication
apply_filters('jwt_auth_before_authenticate', $user)

// Modify token data before update
apply_filters('jwt_auth_jwt_token_data_before_update', $tokenData)
```

#### Token Settings

```php
// Change JWT algorithm
apply_filters('jwt_auth_algorithm', 'HS256')

// Modify token expiration
apply_filters('jwt_auth_expire', $expiration, $issuedAt)

// Modify refresh token
apply_filters('jwt_auth_refresh_token', $token)

// Change refresh token expiration
apply_filters('jwt_auth_refresh_token_expiration', $expiration)
```

#### Security

```php
// Modify security headers
apply_filters('jwt_auth_security_headers', $headers)

// Customize CORS headers
apply_filters('jwt_auth_cors_allow_headers', $headers)
```

#### User Actions

```php
// Control token revocation on password change
apply_filters('jwt_auth_pro_revoke_tokens_on_password_change', true)

// Control token revocation on email change
apply_filters('jwt_auth_pro_revoke_tokens_on_email_change', true)

// Control token revocation on role change
apply_filters('jwt_auth_pro_revoke_tokens_on_role_change', true)
```

#### Analytics

```php
// Control IP anonymization
apply_filters('jwt_auth_pro_anonymize_ip', false)

// Modify analytics retention options
apply_filters('jwt_auth_pro_analytics_retention_options', $options)
```

#### Error Handling

```php
// Customize error messages
apply_filters('jwt_auth_error_messages', $messages)

// Modify error status codes
apply_filters('jwt_auth_error_status', $status, $errorCode)
```

## Frontend Integration

The plugin includes a modern admin interface built with:

- TypeScript
- React
- Shadcn UI
- Radix UI
- Tailwind CSS

All Tailwind classes use the `jwt-` prefix to avoid conflicts.

## Security Features

- Rate limiting
- Token revocation
- Secure headers
- CORS support
- IP-based security
- Token tracking and management

## Support

For support, please visit our website or contact our support team.

## Credits

Developed by [Tmeister](https://enriquechavez.co)
