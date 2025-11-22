# Testing Guide for PR: Fix WordPress Notices and Errors

This guide helps you test the fixes made to resolve WordPress notices and errors after PR#3 merge.

## Prerequisites

Before testing, ensure you have:
- WordPress 6.8+ with WP_DEBUG enabled
- WooCommerce installed and activated
- Access to wp-content/debug.log

## Enable Debug Mode

Add these lines to your `wp-config.php` (for testing only):

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**⚠️ IMPORTANT:** Remove these settings after testing is complete.

## Test Cases

### Test 1: Verify No wpdb::prepare Warnings

**Steps:**
1. Clear your debug.log file: `> wp-content/debug.log`
2. Visit your WordPress admin dashboard
3. Navigate to any Tabesh plugin page
4. Activate/deactivate the Tabesh plugin
5. Check debug.log for any `wpdb::prepare was called incorrectly` messages

**Expected Result:**
- No wpdb::prepare warnings should appear
- No "wpdb::prepare was called incorrectly" messages

### Test 2: Verify Textdomain Loading

**Steps:**
1. Clear debug.log
2. Reload any page on your site
3. Check debug.log for `_load_textdomain_just_in_time` warnings

**Expected Result:**
- No textdomain warnings
- Translation strings should work correctly

### Test 3: Verify Conditional Asset Loading

**Test 3a: Page WITHOUT Tabesh Shortcodes**

**Steps:**
1. Create a new page without any Tabesh shortcodes
2. View the page source (right-click → View Page Source)
3. Search for "tabesh-frontend.css" and "tabesh-staff-panel.css"

**Expected Result:**
- No Tabesh CSS or JS files should be loaded
- Cleaner HTML, better performance

**Test 3b: Page WITH Staff Panel Shortcode**

**Steps:**
1. Create a new page and add the shortcode: `[tabesh_staff_panel]`
2. Publish and view the page
3. View page source and search for "tabesh-staff-panel.css"

**Expected Result:**
- tabesh-frontend.css should be loaded
- tabesh-staff-panel.css should be loaded
- tabesh-staff-panel.js should be loaded
- Staff panel should display correctly with proper styling

**Test 3c: Page WITH Order Form Shortcode**

**Steps:**
1. Create a new page and add the shortcode: `[tabesh_order_form]`
2. Publish and view the page
3. View page source

**Expected Result:**
- tabesh-frontend.css should be loaded
- tabesh-staff-panel.css should NOT be loaded (unnecessary for order form)
- Order form should display correctly

### Test 4: Verify REST API Routes

**Steps:**
1. Open browser developer tools (F12)
2. Go to Network tab
3. Visit a page with `[tabesh_staff_panel]` shortcode
4. Perform an action that calls the REST API (e.g., search orders)
5. Check the network requests

**Expected Result:**
- REST API calls to `/wp-json/tabesh/v1/*` should return 200 OK (not 403 or 500)
- No "permission_callback" warnings in debug.log
- API requests should work properly

### Test 5: Verify Database Schema Updates Are Safe

**Steps:**
1. Note your current database version: Check `wp_options` table for `tabesh_db_version`
2. Deactivate and reactivate the Tabesh plugin
3. Check debug.log for any database errors
4. Verify the database version was updated

**Expected Result:**
- No database errors in debug.log
- Messages like "Tabesh: book_title column already exists" (idempotent)
- Plugin activation completes successfully
- All database columns exist and are correct

### Test 6: Check for Priority/Float Conversion Warnings

**Steps:**
1. Clear debug.log
2. Reload any page on your site
3. Check debug.log for "Implicit conversion from float to int" warnings

**Expected Result:**
- No float to int conversion warnings related to Tabesh plugin
- All add_action/add_filter priorities should be integers

### Test 7: Overall Plugin Functionality

**Steps:**
1. Test creating a new order via the order form
2. Test viewing orders as a customer (user orders shortcode)
3. Test staff panel functionality (if you have staff access)
4. Test admin dashboard functionality

**Expected Result:**
- All features should work as before
- No JavaScript errors in browser console
- No PHP errors in debug.log
- All shortcodes render correctly
- Styles are applied properly without conflicts

## Debug Log Analysis

After running all tests, review your debug.log file. You should **NOT** see:

- ❌ `_load_textdomain_just_in_time was called incorrectly`
- ❌ `Deprecated: Implicit conversion from float to int`
- ❌ `PHP Notice: register_rest_route missing permission_callback`
- ❌ `wpdb::prepare was called incorrectly`

You **MAY** see (these are normal):

- ✅ `Tabesh: Starting database schema update check`
- ✅ `Tabesh: book_title column already exists`
- ✅ `Tabesh: Database schema update completed`

## Performance Testing

**Before and After Comparison:**

1. Test page load time on a page WITHOUT Tabesh shortcodes:
   - Before: All Tabesh CSS/JS loaded (~50KB)
   - After: No Tabesh assets loaded (0KB) ✅

2. Test page load time on a page WITH only order form:
   - Before: All assets loaded (~50KB)
   - After: Only frontend assets loaded (~6KB) ✅

3. Check Network tab in browser DevTools:
   - Count number of HTTP requests
   - Measure total page size
   - Verify only necessary assets are loaded

## Common Issues and Solutions

### Issue: Shortcode not displaying
**Solution:** Clear WordPress cache and page cache

### Issue: Styles not applying
**Solution:** Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)

### Issue: Still seeing warnings
**Solution:** Make sure you're on the correct branch and changes are applied

## Reporting Issues

If you find any issues during testing, please report them with:
1. The exact error message from debug.log
2. Steps to reproduce
3. Screenshots if applicable
4. WordPress version and PHP version
5. Other active plugins

## Clean Up After Testing

1. Remove debug mode from wp-config.php:
   ```php
   // Remove or comment out these lines:
   // define('WP_DEBUG', true);
   // define('WP_DEBUG_LOG', true);
   // define('WP_DEBUG_DISPLAY', false);
   ```

2. Clear debug.log: `> wp-content/debug.log`

3. Clear all caches (WordPress cache, page cache, browser cache)

## Success Criteria

All tests pass when:
- ✅ No PHP notices or warnings in debug.log
- ✅ Conditional asset loading works (verified in page source)
- ✅ All REST API routes work correctly
- ✅ Database updates are idempotent
- ✅ Plugin functionality is unchanged
- ✅ Performance is improved (fewer assets loaded)
- ✅ No conflicts with other plugins

---

**Version:** 1.0.2  
**PR Branch:** fix/wp-notices-rest-prepare-shortcode  
**Date:** 2025-11-22
