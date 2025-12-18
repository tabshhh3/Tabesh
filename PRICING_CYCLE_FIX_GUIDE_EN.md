# Pricing Cycle Fix Guide

## Problem Summary

The pricing cycle in Tabesh had a critical flaw that caused failure in the process from price registration to order submission.

### Root Cause

**Inconsistency between product parameters and pricing matrices** caused:

1. **Pricing Form** (`[tabesh_product_pricing]`):
   - Displayed book sizes in dropdown that weren't in product parameters
   - But rejected them during save (validation failure)
   - Result: "Orphaned" pricing matrices were created

2. **Order Form V2** (`[tabesh_order_form_v2]`):
   - Couldn't retrieve book sizes
   - Showed error "No book sizes configured"
   - The cycle was broken

## Implemented Solution

### 1. Fixed Source of Truth

**Before:**
```php
private function get_all_book_sizes() {
    $configured_sizes = $this->pricing_engine->get_configured_book_sizes(); // From matrices
    $admin_sizes = $this->get_valid_book_sizes_from_settings(); // From settings
    
    // Problem: Merging both sources
    $all_sizes = array_merge($configured_sizes, $admin_sizes);
    return $all_sizes;
}
```

**After:**
```php
private function get_all_book_sizes() {
    // Only from product parameters (source of truth)
    $admin_sizes = $this->get_valid_book_sizes_from_settings();
    return $admin_sizes;
}
```

### 2. Added Automatic Cleanup

New method `cleanup_orphaned_pricing_matrices()`:
- Runs when pricing form loads
- Removes orphaned matrices (for invalid sizes)
- Uses bulk delete for efficiency
- Clears cache after cleanup

### 3. Improved Error Handling

**Added comprehensive logging:**
```php
error_log(sprintf(
    'Tabesh: Size "%s" is available - %d papers, %d bindings',
    $size,
    count($papers),
    count($bindings)
));
```

**Improved error messages in order form:**
- Step-by-step guidance for admins
- Direct links to relevant pages
- Clear explanation of problem and solution

### 4. Diagnostic Tool

File `diagnostic-pricing-cycle.php` for troubleshooting:

**How to use:**
1. Place file in plugin folder
2. Navigate to `/wp-content/plugins/Tabesh/diagnostic-pricing-cycle.php`
3. View complete cycle status
4. Follow recommendations to fix issues

**Information displayed:**
- ✅ Book sizes in product parameters
- ✅ Stored pricing matrices
- ✅ Pricing engine status
- ✅ Available sizes from Constraint Manager
- ✅ Data consistency analysis
- ✅ Fix recommendations

## Installation and Setup Guide

### Step 1: Enable Pricing Engine V2

1. Go to **Product Pricing Management**
2. Click **Enable New Engine**
3. Wait for success confirmation

### Step 2: Configure Book Sizes

1. Go to **Product Settings**
2. Find **Book Sizes** section
3. Add desired sizes (e.g., A5, A4, etc.)
4. Save

**Important:** This is the source of truth. Only sizes defined here can be priced.

### Step 3: Set Prices for Each Size

1. Go to **Product Pricing Management**
2. Select desired size
3. Configure prices:
   - Per-page cost (paper + print)
   - Binding and cover cost
   - Extra services
   - Profit margin
   - Quantity constraints
4. Save

**Note:** Configure a pricing matrix for each size in product settings.

### Step 4: Test Order Form

1. Create a new page
2. Add shortcode `[tabesh_order_form_v2]`
3. View page
4. Should see configured sizes

## Troubleshooting

### Issue: "No book sizes configured"

**Possible solutions:**

1. **Check product parameters:**
   ```
   Settings > Book Sizes
   Any sizes defined?
   ```

2. **Check pricing engine:**
   ```
   Pricing Management > Engine Status
   Is V2 enabled?
   ```

3. **Check pricing matrices:**
   ```
   For each size in product settings,
   Is pricing matrix configured?
   ```

4. **Run automatic diagnostic:**
   ```
   Navigate to diagnostic-pricing-cycle.php
   Follow system recommendations
   ```

### Issue: A size doesn't appear

**Possible causes:**
- Size not in product settings → Add it
- No pricing matrix for size → Configure it
- Corrupted pricing matrix → Automatically cleaned

**Solution:**
1. Go to pricing form (automatic cleanup runs)
2. Select size from dropdown
3. If size not in dropdown, add it to product settings
4. Configure and save prices

### Issue: Orphaned Matrices

**Symptoms:**
- Size shown in pricing form dropdown
- But gives error when saving
- Or doesn't appear in order form

**Automatic fix:**
- Load pricing form
- System automatically removes orphaned matrices
- Check logs (if WP_DEBUG enabled)

## Security Notes

### Sensitive Data

This bug fix includes these security measures:

✅ **Sanitization**: All user inputs are sanitized
✅ **Escaping**: All outputs are escaped  
✅ **Nonce Verification**: All forms have nonces
✅ **Prepared Statements**: All queries use `$wpdb->prepare()`
✅ **Validation**: Only valid sizes can be saved

### Logging

Debug logs only activate when `WP_DEBUG` is set:

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Tabesh: Debug message');
}
```

**Warning:** Disable `WP_DEBUG` in production environments.

## Before vs After

### Before Fix ❌

```
Admin → Pricing Form → Save price for size X
         ↓
    (Size X not in product settings)
         ↓
    Error: Invalid size
         ↓
    Orphaned matrix in database
         ↓
    Order form can't find size
         ↓
    Broken cycle ❌
```

### After Fix ✅

```
Admin → Pricing Form → (Auto-cleanup orphaned matrices)
         ↓
    Only product settings sizes shown
         ↓
    Save price for size X
         ↓
    Validation: Size X in product settings ✓
         ↓
    Pricing matrix saved
         ↓
    Order form shows size X
         ↓
    Customer can place order
         ↓
    Complete cycle ✅
```

## FAQ

### How do I know the cycle is working?

1. Navigate to `diagnostic-pricing-cycle.php`
2. If you see **"Everything is working correctly"**, all is OK
3. Check number of active sizes

### Will previous data be deleted?

**No.** Only orphaned matrices (for invalid sizes) are removed.
Valid matrices (for sizes in settings) are preserved.

### Do I need a backup?

**Yes, always!** Backup before any changes:
```bash
# Backup database
mysqldump -u username -p database_name > backup.sql

# Backup files
zip -r tabesh-backup.zip wp-content/plugins/Tabesh/
```

### How do I identify orphaned matrices?

Use the diagnostic tool:
```
diagnostic-pricing-cycle.php
↓
"Corrupted/Invalid sizes" section
```

### Does this fix affect previous orders?

**No.** Existing orders remain unchanged.
Only affects new order submission process.

## Support

If issues occur:

1. **Enable WP_DEBUG:**
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Check logs:**
   ```
   wp-content/debug.log
   ```

3. **Run diagnostic:**
   ```
   diagnostic-pricing-cycle.php
   ```

4. **Send information:**
   - Debug logs
   - Diagnostic output
   - Error screenshots

## Preventing Future Orphaned Matrices

System automatically prevents orphaned matrix creation:

1. **Stronger validation:** Only valid sizes can be saved
2. **Automatic cleanup:** Orphaned matrices removed on form load
3. **Single source of truth:** Only product settings used

## Conclusion

This fix completely repairs the pricing cycle and prevents future issues.

**Before:**
- Orphaned matrices
- Data inconsistency
- Broken order form
- Failed cycle

**After:**
- Single data source (source of truth)
- Automatic cleanup
- Clear error messages
- Complete and functional cycle

✅ **The pricing cycle is now fully operational!**
