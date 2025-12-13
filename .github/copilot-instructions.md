# GitHub Copilot Instructions for Tabesh

## Quick Reference

**Most Common Commands:**
```bash
# Lint your changes
composer phpcs

# Auto-fix code style issues
composer phpcbf

# Install dependencies (first time setup)
composer install
```

**Before submitting your changes:**
1. Run `composer phpcs` on modified files
2. Fix any linting errors in code you changed
3. Test in RTL mode (Persian language)
4. Test responsive design on mobile
5. Verify all security measures (nonces, sanitization, escaping)

**Key Security Rules (ALWAYS follow):**
- Sanitize ALL user input: `sanitize_text_field()`, `sanitize_email()`, `intval()`
- Escape ALL output: `esc_html()`, `esc_attr()`, `esc_url()`
- Use nonces for ALL forms: `wp_verify_nonce()`
- Use prepared statements for ALL queries: `$wpdb->prepare()`

---

## Project Overview

Tabesh is a comprehensive WordPress plugin for managing book printing orders with full WooCommerce integration. It provides a complete order lifecycle management system with SMS notifications, dynamic price calculation, and role-based access control for Admins, Staff, and Customers.

**Key Technologies:**
- WordPress 6.8+
- PHP 8.2.2+
- WooCommerce (latest version)
- MySQL/MariaDB
- RTL (Right-to-Left) support for Persian language

**Development Workflow:**
1. This is a WordPress plugin - it requires a WordPress installation to run
2. Code style is enforced via PHP CodeSniffer (run `composer phpcs`)
3. No automated unit tests - manual testing is required
4. All changes must maintain RTL support for Persian language
5. Security is paramount - follow WordPress security best practices

**Getting Started:**
```bash
# Install dependencies (PHP CodeSniffer, WPCS)
composer install

# Run linting to check code style
composer phpcs

# Fix auto-fixable code style issues
composer phpcbf
```

## Architecture

### Directory Structure
```
Tabesh/
├── assets/
│   ├── css/          # Stylesheets (frontend.css, admin.css)
│   └── js/           # JavaScript files (frontend.js, admin.js)
├── includes/         # PHP classes
│   ├── class-tabesh-admin.php
│   ├── class-tabesh-notifications.php
│   ├── class-tabesh-order.php
│   ├── class-tabesh-staff.php
│   ├── class-tabesh-user.php
│   └── class-tabesh-woocommerce.php
├── templates/        # Template files
│   ├── admin-archived.php
│   ├── admin-dashboard.php
│   ├── admin-orders.php
│   ├── admin-settings.php
│   ├── order-form.php
│   ├── shortcode-admin-dashboard.php
│   ├── staff-panel.php
│   └── user-orders.php
├── tabesh.php        # Main plugin file
└── README.md         # Documentation
```

### Database Tables
- `wp_tabesh_orders` - Store all order data
- `wp_tabesh_settings` - Plugin configuration
- `wp_tabesh_logs` - Activity logs

### File Organization

**Where to put new code:**
- **Core functionality** (WordPress integration, installation): `includes/core/`
- **Business logic** (orders, admin operations, notifications): `includes/handlers/`
- **Utilities** (validation, security, helpers): `includes/utils/`
- **Security features**: `includes/security/`
- **Admin templates**: `templates/admin/`
- **Frontend templates**: `templates/frontend/`
- **Reusable components**: `templates/partials/`

**File naming convention:**
- Classes: `class-tabesh-{name}.php` (e.g., `class-tabesh-order.php`)
- Templates: `{name}.php` (e.g., `admin-dashboard.php`)
- Use lowercase with hyphens, not underscores

**Class naming convention:**
- `Tabesh_{Name}` (PascalCase) - e.g., `Tabesh_Order`, `Tabesh_Admin`
- Class name should match filename (without `class-` prefix)

## Coding Standards

### PHP Standards

1. **Follow WordPress Coding Standards**
   - Use WordPress core functions instead of PHP equivalents when available
   - Follow WordPress naming conventions (snake_case for functions, PascalCase for classes)
   - Always check for ABSPATH at the start of files: `if (!defined('ABSPATH')) { exit; }`

2. **Security First**
   - **Always sanitize input**: Use `sanitize_text_field()`, `sanitize_email()`, `intval()`, etc.
     ```php
     // Good
     $name = sanitize_text_field($_POST['name']);
     $email = sanitize_email($_POST['email']);
     $count = intval($_POST['count']);
     
     // Bad - never use raw input
     $name = $_POST['name'];  // WRONG!
     ```
   - **Always escape output**: Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`, etc.
     ```php
     // Good
     echo esc_html($user_input);
     echo '<a href="' . esc_url($link) . '">' . esc_html($text) . '</a>';
     
     // Bad - unescaped output is a security risk
     echo $user_input;  // WRONG!
     ```
   - **Use nonces**: Verify nonces for all form submissions with `wp_verify_nonce()`
     ```php
     // Good - verify nonce
     if (!isset($_POST['tabesh_nonce']) || 
         !wp_verify_nonce($_POST['tabesh_nonce'], 'tabesh_action')) {
         wp_die(__('Security check failed', 'tabesh'));
     }
     
     // Bad - accepting form submissions without nonce verification
     if (isset($_POST['submit'])) { ... }  // WRONG!
     ```
   - **Use prepared statements**: Always use `$wpdb->prepare()` for database queries
     ```php
     // Good
     $wpdb->get_results($wpdb->prepare(
         "SELECT * FROM {$wpdb->prefix}tabesh_orders WHERE id = %d",
         $order_id
     ));
     
     // Bad - vulnerable to SQL injection
     $wpdb->get_results("SELECT * FROM wp_tabesh_orders WHERE id = $order_id");  // WRONG!
     ```
   - **Never use direct SQL**: Always use WordPress $wpdb class methods

3. **Code Organization**
   - One class per file
   - Class names should match filename (e.g., `class-tabesh-order.php` contains `Tabesh_Order`)
   - Group related functionality into methods
   - Use meaningful variable and function names

4. **Documentation**
   - Add PHPDoc comments for all classes and methods
   - Include `@package Tabesh` in file headers
   - Document parameters with `@param` and return values with `@return`
   - Add inline comments for complex logic

5. **Error Handling**
   - Use WordPress native logging when `WP_DEBUG_LOG` is enabled: `error_log()` writes to `wp-content/debug.log`
   - Use custom logging to `wp_tabesh_logs` table for production tracking
   - Return WP_Error objects for recoverable errors
   - Use `wp_die()` for fatal errors that should halt execution
   - Provide user-friendly error messages in Persian and English
   - Never log sensitive information (passwords, API keys, personal data)

### CSS Standards

1. **RTL Support**
   - This plugin requires full RTL (Right-to-Left) support for Persian language
   - Use logical properties where possible (e.g., `margin-inline-start` instead of `margin-left`)
   - Test all layouts in RTL mode
   - Use BEM naming convention where applicable

2. **Responsive Design**
   - Mobile-first approach
   - Test on various screen sizes
   - Use WordPress core CSS classes when appropriate

### JavaScript Standards

1. **Modern JavaScript**
   - Use ES6+ features
   - Use `const` and `let` instead of `var`
   - Use arrow functions where appropriate
   - Use template literals for string concatenation

2. **WordPress Integration**
   - Use `wp_localize_script()` to pass data from PHP to JavaScript
   - Use `wp_enqueue_script()` and `wp_enqueue_style()` for assets
   - Handle AJAX with WordPress REST API endpoints

3. **AJAX Requests**
   - All AJAX requests should go through REST API endpoints in `/wp-json/tabesh/v1/`
   - Always include nonces for authenticated requests
   - Handle errors gracefully with user-friendly messages

## API Endpoints

### REST API Routes
All routes are prefixed with `/wp-json/tabesh/v1/`

- **POST** `/calculate-price` - Calculate order price
  ```javascript
  // Example usage
  fetch('/wp-json/tabesh/v1/calculate-price', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': tabeshData.nonce
      },
      body: JSON.stringify({
          book_size: 'وزیری',
          pages: 200,
          paper_type: 'گلاسه',
          // ... other parameters
      })
  });
  ```

- **POST** `/submit-order` - Submit new order (requires authentication)
  ```javascript
  // Must be logged in, nonce required
  fetch('/wp-json/tabesh/v1/submit-order', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': tabeshData.nonce
      },
      body: JSON.stringify(orderData)
  });
  ```

- **POST** `/update-status` - Update order status (requires permission)
  ```javascript
  // Requires edit_shop_orders capability
  fetch('/wp-json/tabesh/v1/update-status', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': tabeshData.nonce
      },
      body: JSON.stringify({
          order_id: 123,
          new_status: 'processing'
      })
  });
  ```

When creating new endpoints:
- Register routes in the main plugin file (`tabesh.php`) or dedicated REST controller
- Validate permissions using `current_user_can()`
- Sanitize all inputs using WordPress sanitization functions
- Return consistent JSON responses with proper HTTP status codes
  ```php
  // Example endpoint registration
  register_rest_route('tabesh/v1', '/my-endpoint', array(
      'methods' => 'POST',
      'callback' => 'my_callback_function',
      'permission_callback' => function() {
          return current_user_can('edit_shop_orders');
      }
  ));
  ```

## Features and Components

### Price Calculator
- Located in `class-tabesh-order.php` → `calculate_price()` method
- Comprehensive algorithm considering: book size, paper type, page count, binding, options, quantity
- Formula: `FinalPrice = (((PaperCost + PrintCost) * PageCount) + CoverCost + BindingCost + OptionsCost) * Quantity * (1 + ProfitMargin)`

### Order Management
- Orders are stored in custom database table `wp_tabesh_orders`
- Order statuses: pending, processing, completed, cancelled, archived
- Each status change triggers notifications (SMS and email)

### Notifications
- SMS via MelliPayamak API (class-tabesh-notifications.php)
- Email notifications for all order updates
- Configurable notification triggers in settings

### Role-Based Access
- **Admin** (`manage_woocommerce` capability): Full access to all features
- **Staff** (`edit_shop_orders` capability): Can update order statuses
- **Customer** (logged-in users): Can view their own orders

### Shortcodes
- `[tabesh_order_form]` - Display order submission form
- `[tabesh_user_orders]` - Show user's orders
- `[tabesh_staff_panel]` - Display staff management panel
- `[tabesh_admin_dashboard]` - Show admin dashboard overview

## Development Guidelines

### Before Making Changes

1. **Understand the Workflow**
   - Run `composer phpcs` first to see baseline linting issues
   - You are NOT responsible for fixing pre-existing issues
   - Only fix linting issues in code you modify

2. **Understand Project Dependencies**
   - This is a WordPress plugin - WordPress must be running
   - Requires WooCommerce plugin to be active
   - Cannot run standalone or be tested without WordPress environment

### Adding New Features

1. **Plan First**
   - Consider impact on existing functionality
   - Check if WordPress or WooCommerce already provides similar functionality
   - Plan database changes carefully (migrations may be needed)

2. **Follow the Pattern**
   - Use existing code structure as a template
   - Keep consistent with current naming conventions
   - Add proper hooks and filters for extensibility

3. **Test Thoroughly**
   - Test with different user roles
   - Test in RTL mode
   - Test responsive design on mobile
   - Test with WooCommerce integration
   - Verify all security measures
   - Run `composer phpcs` on modified files

### Modifying Existing Features

1. **Minimal Changes**
   - Make the smallest possible change to achieve the goal
   - Don't refactor unrelated code unless fixing security issues
   - Keep backward compatibility when possible
   - Run `composer phpcs` on modified files only

2. **Database Changes**
   - Never modify existing columns
   - Add new columns if needed
   - Include migration scripts if structure changes

3. **Settings Changes**
   - Settings are stored in `wp_tabesh_settings` table
   - Always provide default values
   - Validate and sanitize settings on save

### What NOT to Change

1. **Don't fix unrelated issues**
   - If there are pre-existing bugs or linting errors in code you're not modifying, leave them alone
   - Focus only on the specific issue you're addressing
   
2. **Don't modify working WordPress integration**
   - The plugin integrates with WordPress and WooCommerce - don't change core integration points unless that's specifically your task
   
3. **Don't change database structure without migration**
   - Any database changes require migration scripts
   - See `migration-convert-settings-to-json.php` as an example

4. **Don't remove or modify existing hooks/filters**
   - Other code may depend on them
   - Add new ones if needed, but keep existing ones for backward compatibility

## Testing

### Linting and Code Quality

The project uses PHP CodeSniffer with WordPress Coding Standards. Always run linting before committing:

```bash
# Run linting (checks code style and security issues)
composer phpcs

# Auto-fix code style issues where possible
composer phpcbf
```

**Important**: Fix any linting errors in code you modify. You are NOT responsible for fixing pre-existing linting errors in unrelated code.

### Manual Testing Checklist

Since this project does not have automated unit tests, manual testing is critical:

- [ ] Test with fresh WordPress installation (or use existing dev environment)
- [ ] Verify WooCommerce integration works
- [ ] Test all user roles (Admin, Staff, Customer)
- [ ] Test RTL layout rendering (essential for Persian language support)
- [ ] Test responsive design on mobile
- [ ] Verify SMS notifications (if configured)
- [ ] Test order submission flow
- [ ] Test price calculation with various parameters
- [ ] Check security (nonces, sanitization, escaping)
- [ ] Review database queries for SQL injection vulnerabilities
- [ ] Run `composer phpcs` on modified files

### Browser Testing
- Test in Chrome, Firefox, Safari
- Test on mobile browsers (especially for RTL rendering)
- Test with browser console open (F12) - check for JavaScript errors
- Test with both LTR and RTL layouts

## Common Pitfalls to Avoid

1. **Don't bypass WordPress functions**
   - Use WordPress APIs instead of raw PHP functions
   - Example: Use `wp_remote_get()` instead of `file_get_contents()`
   ```php
   // Good - WordPress way
   $response = wp_remote_get('https://api.example.com/data');
   if (!is_wp_error($response)) {
       $body = wp_remote_retrieve_body($response);
   }
   
   // Bad - raw PHP
   $data = file_get_contents('https://api.example.com/data');  // WRONG!
   ```

2. **Don't ignore RTL support**
   - This plugin MUST support RTL for Persian language
   - Use logical properties where possible
   - Always test in RTL mode
   ```css
   /* Good - RTL-safe */
   margin-inline-start: 10px;
   padding-inline-end: 20px;
   
   /* Less ideal - requires RTL override */
   margin-left: 10px;  /* Will need RTL-specific rule */
   ```
   - Test in Persian language mode by adding to wp-config.php: `define('WPLANG', 'fa_IR');`

3. **Don't hardcode values**
   - Use settings and configuration options
   - Make values translatable with `__()` or `_e()`
   ```php
   // Good - translatable and configurable
   $message = __('Order submitted successfully', 'tabesh');
   $price_per_page = get_option('tabesh_price_per_page', 100);
   
   // Bad - hardcoded
   $message = 'Order submitted successfully';  // WRONG! Not translatable
   $price_per_page = 100;  // WRONG! Not configurable
   ```

4. **Don't forget WooCommerce compatibility**
   - This plugin integrates with WooCommerce
   - Check WooCommerce functions availability before using
   ```php
   // Good - check if WooCommerce is active
   if (class_exists('WooCommerce')) {
       // Use WooCommerce functions
   }
   
   // Bad - assuming WooCommerce exists
   WC()->cart->add_to_cart($product_id);  // WRONG! May cause fatal error
   ```

5. **Don't skip security measures**
   - Every user input must be sanitized
   - Every output must be escaped
   - Every form must use nonces
   - See "Security First" section above for examples

## Debugging

### Enable Debug Mode
Add to `wp-config.php` for **development environments only**:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**⚠️ SECURITY WARNING**: Never enable `WP_DEBUG` or `WP_DEBUG_LOG` in production environments as they can:
- Expose sensitive information (file paths, database queries, API calls)
- Impact performance significantly
- Fill up disk space with log files
- Reveal security vulnerabilities to potential attackers

### Debug Tools
- `error_log()` - Writes to `wp-content/debug.log` (only when `WP_DEBUG_LOG` is enabled)
  ```php
  error_log('Debug message: ' . print_r($variable, true));
  ```
- Browser console - Check JavaScript errors (F12 → Console tab)
  ```javascript
  console.log('Debug:', variable);
  console.error('Error:', errorMessage);
  ```
- `tabesh-diagnostic.php` - **For development only**: Upload to a protected directory with `.htaccess` or use within WordPress admin interface. Never leave diagnostic tools in publicly accessible locations.
  - This file helps diagnose settings and configuration issues
  - Shows database structure, settings values, and system information
  - **NEVER** use in production - contains sensitive information

### Debugging Workflow
1. Enable `WP_DEBUG_LOG` in development
2. Reproduce the issue
3. Check `wp-content/debug.log` for errors
4. Check browser console for JavaScript errors
5. Use `error_log()` to add debug statements
6. Use `tabesh-diagnostic.php` for configuration issues
7. **Remember to disable debugging before deploying to production**

### Common Issues
- **Settings not saving**: Check database permissions and debug logs
  ```php
  // Add debug logging to investigate
  error_log('Settings before save: ' . print_r($settings, true));
  $result = update_option('tabesh_settings', $settings);
  error_log('Update result: ' . ($result ? 'success' : 'failed'));
  ```
- **Price calculation errors**: Verify all pricing fields format (`key=value`)
  - Check `includes/handlers/class-tabesh-order.php` → `calculate_price()` method
  - Ensure all pricing parameters are numeric
- **SMS not sending**: Check MelliPayamak credentials and balance
  - Check `includes/handlers/class-tabesh-sms.php` for SMS API integration
  - Verify API credentials in Settings → SMS
  - Check debug log for API response

## Internationalization (i18n)

- Text domain: `tabesh`
- Language files location: `/languages/`
- Always use translation functions:
  - `__('text', 'tabesh')` - Returns translated text
  - `_e('text', 'tabesh')` - Echoes translated text
  - `esc_html__('text', 'tabesh')` - Returns escaped translated text
  - `esc_html_e('text', 'tabesh')` - Echoes escaped translated text

## Performance Considerations

- Cache expensive database queries
- Minimize database queries in loops
- Use transients for temporary data storage
- Optimize asset loading (combine and minify when possible)
- LiteSpeed compatible - avoid unnecessary dynamic content

## Support and Resources

- **Documentation**: See README.md, INSTALL.md, QUICKSTART.md
- **Troubleshooting**: See PRICING_TROUBLESHOOTING.md
- **API Reference**: See API.md
- **Contributing**: See CONTRIBUTING.md
- **WordPress Codex**: https://codex.wordpress.org/
- **WooCommerce Docs**: https://woocommerce.com/documentation/

## License

This plugin is licensed under GPL v2 or later. All contributions must be compatible with this license.
