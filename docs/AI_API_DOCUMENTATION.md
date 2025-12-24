# Tabesh AI System - Technical API Documentation

## Architecture Overview

The Tabesh AI system is built with a modular architecture consisting of four main components:

1. **Configuration Layer** (`Tabesh_AI_Config`) - Manages all system settings
2. **Permissions Layer** (`Tabesh_AI_Permissions`) - Controls access to sensitive data
3. **Driver Layer** (`Tabesh_AI_Gemini`) - Handles communication with Google Gemini API
4. **Controller Layer** (`Tabesh_AI`) - Orchestrates all AI operations

## Class Reference

### Tabesh_AI_Config

Manages AI system configuration and settings storage.

#### Constants

```php
const MODE_DIRECT = 'direct';   // Direct connection to Gemini
const MODE_SERVER = 'server';   // Act as server for external clients
const MODE_CLIENT = 'client';   // Connect to external server
```

#### Methods

##### `get( string $key, mixed $default = null ): mixed`

Get a configuration value.

```php
$api_key = Tabesh_AI_Config::get('gemini_api_key');
$mode = Tabesh_AI_Config::get('mode', 'direct');
```

##### `set( string $key, mixed $value ): bool`

Set a configuration value.

```php
Tabesh_AI_Config::set('enabled', true);
Tabesh_AI_Config::set('allowed_roles', ['administrator', 'customer']);
```

##### `is_enabled(): bool`

Check if AI system is enabled.

```php
if (Tabesh_AI_Config::is_enabled()) {
    // AI is active
}
```

##### `get_mode(): string`

Get current AI mode (direct, server, or client).

```php
$mode = Tabesh_AI_Config::get_mode();
```

##### `user_has_access(): bool`

Check if current user has access to AI system.

```php
if (Tabesh_AI_Config::user_has_access()) {
    // User can use AI
}
```

##### `get_gemini_api_key(): string`

Get the Gemini API key.

```php
$api_key = Tabesh_AI_Config::get_gemini_api_key();
```

##### `get_all(): array`

Get all configuration settings as an array.

```php
$all_settings = Tabesh_AI_Config::get_all();
```

##### `reset(): bool`

Reset all settings to defaults.

```php
Tabesh_AI_Config::reset();
```

##### `validate_api_key( string $api_key ): bool`

Validate API key format.

```php
if (Tabesh_AI_Config::validate_api_key($key)) {
    // Key is valid
}
```

### Tabesh_AI_Permissions

Manages permissions and access control for AI features.

#### Constants

```php
const PERM_ORDERS = 'access_orders';           // Access to order data
const PERM_USERS = 'access_users';             // Access to user data
const PERM_PRICING = 'access_pricing';         // Access to pricing data
const PERM_WOOCOMMERCE = 'access_woocommerce'; // Access to WooCommerce data
```

#### Methods

##### `user_can( string $permission, int $user_id = null ): bool`

Check if user has specific AI permission.

```php
if (Tabesh_AI_Permissions::user_can('access_orders')) {
    // User can access order data
}
```

##### `can_access_orders( int $user_id = null ): bool`

Check if user can access orders data.

```php
if (Tabesh_AI_Permissions::can_access_orders()) {
    // Include order data in AI context
}
```

##### `can_access_users( int $user_id = null ): bool`

Check if user can access users data.

##### `can_access_pricing( int $user_id = null ): bool`

Check if user can access pricing data.

##### `can_access_woocommerce( int $user_id = null ): bool`

Check if user can access WooCommerce data.

##### `filter_data( array $data, int $user_id = null ): array`

Filter data based on user permissions.

```php
$context = [
    'orders' => [...],
    'users' => [...],
    'pricing' => [...]
];

$filtered = Tabesh_AI_Permissions::filter_data($context);
// Only includes data user has access to
```

##### `get_accessible_data_types( int $user_id = null ): array`

Get list of accessible data types for user.

```php
$accessible = Tabesh_AI_Permissions::get_accessible_data_types();
// Returns: ['orders', 'pricing']
```

### Tabesh_AI_Gemini

Driver for Google Gemini AI API communication.

#### Methods

##### `__construct()`

Initialize Gemini driver with API key from config.

```php
$gemini = new Tabesh_AI_Gemini();
```

##### `chat( string $message, array $context = [] ): array|WP_Error`

Send chat message to Gemini AI.

```php
$response = $gemini->chat(
    'قیمت چاپ کتاب چقدر است؟',
    [
        'user_name' => 'محمد',
        'form_data' => [
            'book_size' => 'وزیری',
            'page_count' => '200'
        ]
    ]
);

if (is_wp_error($response)) {
    // Handle error
    $error_message = $response->get_error_message();
} else {
    // Use response
    echo $response['message'];
}
```

**Response Structure:**
```php
[
    'success' => true,
    'message' => 'AI response text',
    'usage' => [
        'promptTokenCount' => 123,
        'candidatesTokenCount' => 456,
        'totalTokenCount' => 579
    ]
]
```

##### `test_connection(): array|WP_Error`

Test API connection.

```php
$result = $gemini->test_connection();

if (is_wp_error($result)) {
    // Connection failed
} else {
    // Connection successful
}
```

### Tabesh_AI

Main AI controller that orchestrates all AI operations.

#### Methods

##### `__construct()`

Initialize AI controller and register hooks.

```php
$ai = new Tabesh_AI();
```

##### `register_rest_routes()`

Register REST API routes for AI system.

Automatically called during `rest_api_init` action.

##### `check_ai_permission( WP_REST_Request $request ): bool|WP_Error`

Permission callback for REST API endpoints.

Returns `true` if user has access, `WP_Error` otherwise.

##### `rest_chat( WP_REST_Request $request ): WP_REST_Response|WP_Error`

Handle chat endpoint requests.

**Endpoint:** `POST /wp-json/tabesh/v1/ai/chat`

**Request:**
```json
{
    "message": "سوال کاربر",
    "context": {
        "form_data": {...}
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "پاسخ AI"
}
```

##### `rest_get_form_data( WP_REST_Request $request ): WP_REST_Response|WP_Error`

Get available form options.

**Endpoint:** `GET /wp-json/tabesh/v1/ai/form-data`

**Response:**
```json
{
    "success": true,
    "data": {
        "book_sizes": ["وزیری", "رقعی"],
        "paper_types": {...},
        "print_types": [...],
        "binding_types": [...]
    }
}
```

##### `rest_forward_request( WP_REST_Request $request ): WP_REST_Response|WP_Error`

Forward request to external server (client mode).

**Endpoint:** `POST /wp-json/tabesh/v1/ai/forward`

##### `render_chat_interface( array $atts ): string`

Render chat interface shortcode.

**Shortcode:** `[tabesh_ai_chat]`

Returns HTML string for chat interface.

## REST API Reference

### Authentication

All endpoints require WordPress authentication. Include nonce in header:

```javascript
headers: {
    'X-WP-Nonce': wpApiSettings.nonce
}
```

### Endpoints

#### POST /wp-json/tabesh/v1/ai/chat

Send message to AI assistant.

**Permission:** User must have AI access

**Request Body:**
```json
{
    "message": "string (required)",
    "context": {
        "form_data": "object (optional)",
        "user_name": "string (optional)"
    }
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "AI response text",
    "usage": {
        "promptTokenCount": 123,
        "candidatesTokenCount": 456,
        "totalTokenCount": 579
    }
}
```

**Error Response (403):**
```json
{
    "code": "ai_disabled",
    "message": "سیستم هوش مصنوعی غیرفعال است",
    "data": {
        "status": 403
    }
}
```

#### GET /wp-json/tabesh/v1/ai/form-data

Get available form options for AI context.

**Permission:** User must have AI access

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "book_sizes": ["وزیری", "رقعی", "A4", "B5"],
        "paper_types": {
            "گلاسه": ["80", "100", "120"],
            "تحریر": ["70", "80"]
        },
        "print_types": ["سیاه و سفید", "رنگی", "ترکیبی"],
        "binding_types": ["گالینگور", "سوئیزی", "سیمی"]
    }
}
```

#### POST /wp-json/tabesh/v1/ai/forward

Forward request to external AI server (client mode only).

**Permission:** User must have AI access, system must be in client mode

**Request/Response:** Same as `/ai/chat` endpoint

## JavaScript API

### Global Object: `tabeshAI`

Localized script object available when chat interface is loaded.

**Properties:**

```javascript
tabeshAI = {
    ajaxUrl: 'https://example.com/wp-json/tabesh/v1/ai',
    nonce: 'abc123...',
    strings: {
        sendButton: 'ارسال',
        placeholder: 'پیام خود را بنویسید...',
        errorMessage: 'خطا در ارسال پیام',
        connecting: 'در حال اتصال...',
        welcomeMessage: 'سلام! چطور می‌تونم کمکتون کنم؟'
    }
}
```

### Usage Example

```javascript
// Send message to AI
jQuery.ajax({
    url: tabeshAI.ajaxUrl + '/chat',
    method: 'POST',
    headers: {
        'X-WP-Nonce': tabeshAI.nonce
    },
    contentType: 'application/json',
    data: JSON.stringify({
        message: 'سوال من',
        context: {
            form_data: {
                book_size: $('#book_size').val(),
                page_count: $('#page_count').val()
            }
        }
    }),
    success: function(response) {
        if (response.success) {
            console.log('AI Response:', response.message);
        }
    },
    error: function(xhr) {
        console.error('Error:', xhr.responseJSON.message);
    }
});
```

## Hooks and Filters

### Actions

**`tabesh_ai_before_chat`**

Fired before processing chat request.

```php
add_action('tabesh_ai_before_chat', function($message, $context) {
    // Custom logic before chat
}, 10, 2);
```

**`tabesh_ai_after_chat`**

Fired after processing chat request.

```php
add_action('tabesh_ai_after_chat', function($response, $message) {
    // Custom logic after chat
}, 10, 2);
```

### Filters

**`tabesh_ai_system_prompt`**

Filter the system prompt sent to AI.

```php
add_filter('tabesh_ai_system_prompt', function($prompt, $context) {
    $prompt .= "\nاطلاعات اضافی: ...";
    return $prompt;
}, 10, 2);
```

**`tabesh_ai_response`**

Filter the AI response before returning.

```php
add_filter('tabesh_ai_response', function($response) {
    // Modify response
    return $response;
});
```

## Database Schema

### Settings Table

AI settings are stored in `wp_tabesh_settings` table with `ai_` prefix.

**Key Settings:**

| Key | Type | Description |
|-----|------|-------------|
| ai_enabled | boolean | AI system enabled |
| ai_mode | string | Operation mode (direct/server/client) |
| ai_gemini_api_key | string | Gemini API key |
| ai_gemini_model | string | Gemini model name |
| ai_server_url | string | External server URL (client mode) |
| ai_server_api_key | string | Server API key (client mode) |
| ai_allowed_roles | array | Allowed user roles |
| ai_access_orders | boolean | Access to orders data |
| ai_access_users | boolean | Access to users data |
| ai_access_pricing | boolean | Access to pricing data |
| ai_access_woocommerce | boolean | Access to WooCommerce data |
| ai_cache_enabled | boolean | Enable response caching |
| ai_cache_ttl | integer | Cache TTL in seconds |
| ai_max_tokens | integer | Max output tokens |
| ai_temperature | float | Temperature (0.0-2.0) |

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| `ai_disabled` | سیستم هوش مصنوعی غیرفعال است | AI system is disabled |
| `no_permission` | شما دسترسی به سیستم هوش مصنوعی ندارید | User lacks AI access |
| `invalid_nonce` | کد امنیتی نامعتبر است | Invalid nonce |
| `no_api_key` | کلید API تنظیم نشده است | API key not configured |
| `api_error` | خطای API: کد %d | API returned error |
| `invalid_response` | پاسخ نامعتبر از API | Invalid API response |
| `invalid_mode` | حالت AI نامعتبر است | Invalid AI mode |
| `no_server_url` | آدرس سرور تنظیم نشده است | Server URL not configured |
| `server_error` | خطای سرور: کد %d | Server returned error |

## Performance Optimization

### Caching

Enable caching to reduce API calls and improve performance:

```php
Tabesh_AI_Config::set('cache_enabled', true);
Tabesh_AI_Config::set('cache_ttl', 3600); // 1 hour
```

Cached responses are stored as WordPress transients.

### Token Optimization

Reduce token usage to lower costs:

```php
Tabesh_AI_Config::set('max_tokens', 1024); // Shorter responses
Tabesh_AI_Config::set('temperature', 0.3); // More focused responses
```

### Model Selection

Choose appropriate model for your needs:

- **gemini-2.0-flash-exp**: Fast, cost-effective (recommended)
- **gemini-1.5-flash**: Balanced performance
- **gemini-1.5-pro**: High quality, slower, more expensive

## Security Best Practices

1. **Limit Access**: Only grant AI access to necessary roles
2. **Restrict Data**: Disable access to sensitive data types
3. **Validate Input**: Always sanitize user input
4. **Use HTTPS**: Ensure secure communication
5. **Monitor Usage**: Track API usage and costs
6. **Rotate Keys**: Regularly update API keys
7. **Audit Logs**: Review permission checks in logs

## Troubleshooting

### Enable Debug Logging

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Logs will be written to `wp-content/debug.log`.

### Common Issues

**"کلید API نامعتبر است"**
- Verify API key in settings
- Check API key is active in Google AI Studio
- Ensure no extra spaces in key

**"خطای اتصال"**
- Check internet connectivity
- Verify firewall settings
- Test API key with Google AI Studio

**"پاسخ نامعتبر"**
- Check API response structure
- Verify model compatibility
- Review debug logs

## Version History

- **1.0.0** (2025-01-24): Initial release
  - Support for Gemini 2.0 Flash
  - Three operation modes (Direct/Server/Client)
  - Persian language support
  - RTL interface
  - Role-based access control

---

For support: support@chapco.ir  
Documentation: https://chapco.ir/docs/ai-system
