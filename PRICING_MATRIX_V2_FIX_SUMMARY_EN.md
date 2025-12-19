# Pricing Matrix V2 Cycle Fix Summary

## Issues Identified and Resolved ✅

### 1. Missing `action` Column in Security Logs Table ❌ → Fixed ✅

**Problem**: 
- The `wp_tabesh_security_logs` table was missing the `action` column
- Code in some places tried to insert data into this column → database error

**Solution**:
- Added `add_action_column_to_security_logs()` method to `Tabesh_Install` class
- This method runs during plugin activation/update
- The `action` column is automatically created

**File**: `includes/core/class-tabesh-install.php` - Lines 440-495

---

### 2. `cleanup_corrupted_matrices()` Method Deleted Valid Matrices ❌ → Fixed ✅

**Root Cause**:
```php
// Old code (incorrect):
if (! in_array($book_size, $valid_sizes, true)) {
    // This matrix is "corrupted" - delete it
    $corrupted_keys[] = $setting_key;
}
```

**Why was this problematic?**
1. When admin configures settings for the first time, `$valid_sizes` is empty
2. Therefore, **all** matrices are considered "corrupted"
3. All saved matrices are deleted!
4. No active book_sizes remain

**Solution**:
```php
// New code (correct):
$decoded = base64_decode($safe_key, true);

// Only if base64 decoding fails or result is invalid:
if (false === $decoded || empty($decoded)) {
    $corrupted_keys[] = $setting_key;
}
elseif (! $this->is_valid_book_size_string($decoded)) {
    $corrupted_keys[] = $setting_key;
}
```

**New Logic**:
- Only matrices with corrupted base64 encoding are deleted
- Matrices with invalid book_size (e.g., containing illegal characters) are deleted
- **No comparison with `book_sizes`** → matrices are preserved

**File**: `includes/handlers/class-tabesh-pricing-engine.php` - Lines 1304-1410

---

### 3. `cleanup_orphaned_pricing_matrices()` Ran on Every Form Load ❌ → Disabled ✅

**Problem**:
- This method ran every time the pricing form loaded
- Had exactly the same issue: comparing with empty `book_sizes` → deleting all matrices

**Solution**:
- This method is **completely disabled**
- Instead, `migrate_mismatched_book_size_keys()` is used, which is more intelligent
- Added log message explaining why it's disabled

**File**: `includes/handlers/class-tabesh-product-pricing.php` - Lines 1056-1078

---

## Correct Pricing Cycle (After Fix) ✅

### Workflow:

```
1. Admin goes to Product Settings
   ↓
   Defines book sizes: ["A5", "رقعی", "وزیری"]
   ↓

2. Admin goes to Product Pricing form
   ↓
   Enables V2 engine
   ↓
   Creates pricing matrix for "A5" (papers + bindings)
   ↓
   Saves
   ↓
   ✅ Matrix saved with normalized base64 key
   ✅ Cleanup only removes truly corrupted matrices (not all!)
   ↓

3. Constraint Manager checks matrices
   ↓
   Gets size list from Product Parameters: ["A5", "رقعی", "وزیری"]
   ↓
   Gets existing matrices from Pricing Engine: ["A5"]
   ↓
   Matches them:
   - A5: has matrix + has papers + has bindings → enabled = true ✅
   - رقعی: no matrix → enabled = false
   - وزیری: no matrix → enabled = false
   ↓

4. Order Form V2 loads
   ↓
   Only "A5" is displayed (enabled) ✅
   ↓
   User can submit order ✅
```

### Before Fix (Broken Cycle):

```
Admin saves matrix
  ↓
cleanup runs
  ↓
book_sizes is empty or being configured
  ↓
All matrices deemed "orphaned"
  ↓
❌ All matrices deleted
  ↓
Order form has no active book_sizes ❌
```

### After Fix (Healthy Cycle):

```
Admin saves matrix
  ↓
cleanup only checks truly corrupted data
  ↓
Valid matrices are preserved ✅
  ↓
Constraint Manager finds matrices ✅
  ↓
Order form displays active book_sizes ✅
```

---

## Testing Guide

### Test 1: Fresh Installation ✅

```bash
# 1. Install/activate plugin
# 2. Go to Settings → Products
#    Add sizes: A5, رقعی, وزیری

# 3. Go to Product Pricing Management
#    Enable V2 engine

# 4. Configure complete pricing matrix for A5:
#    - Papers: تحریر 70, بالک 80, ...
#    - Bindings: شومیز, سیمی, ...
#    - Save

# 5. Open Order Form V2
#    Expected: A5 should be selectable in size list ✅
```

### Test 2: Reconfiguration ✅

```bash
# 1. With existing matrices
# 2. Go to Product Settings
#    Temporarily remove a size (e.g., رقعی)
#    Save

# 3. Re-add the same size
#    Save

# 4. Go to Pricing form
#    Expected: Previous رقعی matrix still exists ✅
#    (not deleted)
```

### Test 3: Legacy Key Migration ✅

```bash
# If you have old matrices with descriptions:
# e.g., "رقعی (14×20)"

# 1. Open Pricing form
#    Expected: Success message shown
#    "✓ اصلاح خودکار ماتریس‌های قیمت"

# 2. Keys are normalized: "رقعی (14×20)" → "رقعی"
# 3. Matrices are merged
# 4. Sizes are activated
```

---

## Useful Debug Logs

With `WP_DEBUG = true`, these messages appear in `wp-content/debug.log`:

### Success Logs:
```
Tabesh: SUCCESS - Added action column to security_logs table
Tabesh: Cleanup complete - No corrupted matrices found
Tabesh: Size "A5" is USABLE and ENABLED - 3 papers, 2 bindings
Tabesh Constraint Manager: Returning 3 total sizes (1 enabled, 2 disabled)
```

### Warning Logs (Normal):
```
Tabesh: cleanup_orphaned_pricing_matrices disabled - using migrate_mismatched_book_size_keys instead
Tabesh: Size "رقعی" exists in product parameters but has no pricing matrix
```

### Error Logs (Need Attention):
```
Tabesh: ERROR - Failed to add action column: [error message]
Tabesh: Found corrupted pricing matrix with invalid encoding
```

---

## Security Notes ✅

All changes:
- ✅ Use prepared statements
- ✅ Sanitize inputs
- ✅ Escape outputs
- ✅ Nonce verification unchanged
- ✅ Only truly corrupted data (invalid encoding) is deleted

---

## File Changes Summary

| File | Change | Reason |
|------|--------|--------|
| `class-tabesh-install.php` | Added `add_action_column_to_security_logs()` | Fix database error |
| `class-tabesh-pricing-engine.php` | Fixed `cleanup_corrupted_matrices()` logic | Preserve valid matrices |
| `class-tabesh-product-pricing.php` | Disabled `cleanup_orphaned_pricing_matrices()` | Prevent incorrect deletion |

---

## Final Result ✅

With these changes:

1. ✅ `action` column exists in security logs table → database error fixed
2. ✅ Pricing matrices are preserved → no incorrect deletion
3. ✅ book_sizes are activated after saving prices → displayed in V2 form
4. ✅ Cache properly invalidated → no stale data used
5. ✅ Complete pricing and order submission cycle is healthy

**V2 cycle is now fully operational! 🎉**
