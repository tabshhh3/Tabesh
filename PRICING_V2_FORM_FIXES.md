# Pricing Form V2 Fixes - Summary

## Issues Addressed

### Issue 1: Enable/Disable Toggles for Paper Weight Printing Types
**Persian:** کلید فعال سازی و غیر فعال سازی گرم کاغذ فقط برای تک جلد قرار دارد باید یک کلید برای فیلد رنگی هم بگزارید

**Status:** ✅ RESOLVED

**Details:**
The admin pricing form template (`templates/admin/product-pricing.php`) already had toggle switches for BOTH black-and-white (تک‌رنگ) AND color printing (رنگی) for each paper weight. However, the restrictions were not being properly applied when building the JavaScript data for frontend forms.

**What was fixed:**
1. Added logic to filter out paper types where BOTH bw and color printing are forbidden (such paper types shouldn't appear in dropdowns at all)
2. Added logic to filter out forbidden binding types from frontend dropdowns
3. Ensured restrictions are respected in both customer order form and admin order form

**Files Modified:**
- `tabesh.php` (lines 2330-2365): Added restrictions filtering when building v2PricingMatrices
- `includes/handlers/class-tabesh-admin-order-form.php` (lines 173-218): Added same restrictions filtering for admin order form

### Issue 2: Paper Weight Dropdown Not Loading
**Persian:** در فرم محاسبه قیمت و ثبت سفارش مشتری تا مرحله انتخاب کاغذ پیش میرود و در مرحله انتخاب گرماژ لود نمیشود و فرم کار نمیکند

**Status:** ✅ RESOLVED

**Root Cause:**
When Pricing Engine V2 is enabled, paper weights are specific to each book size. The JavaScript function `updatePaperWeights()` was not handling the case where a user selects a paper type BEFORE selecting a book size in V2 mode. This caused the paper weight dropdown to remain empty with no error message.

**What was fixed:**
1. Enhanced `updatePaperWeights()` to show a user-friendly message when V2 is enabled but book size is not selected yet
2. Added fallback message when no weights are defined for the selected paper type
3. Improved `updatePaperWeightsV2()` with comprehensive error handling and Persian error messages
4. Added console logging for debugging purposes

**Files Modified:**
- `assets/js/frontend.js` (lines 204-232): Enhanced error handling and user feedback

**Error Messages Added:**
- "لطفاً ابتدا قطع کتاب را انتخاب کنید" - Please select book size first
- "هیچ گرماژی برای این کاغذ تعریف نشده است" - No weights defined for this paper
- "این نوع کاغذ برای قطع انتخابی موجود نیست" - This paper type is not available for selected book size
- "خطا: ماتریس قیمت‌گذاری یافت نشد" - Error: pricing matrix not found

## Technical Details

### Restrictions Filtering Logic

When building the `v2PricingMatrices` data structure for JavaScript, the code now:

1. **Checks for completely forbidden paper types:** If a paper type is in the `forbidden_paper_types` array, it's excluded entirely
2. **Checks for partial restrictions:** If BOTH bw and color printing are forbidden for a paper type, it's excluded
3. **Includes partially allowed paper types:** If at least one print type (bw OR color) is allowed, the paper type is included
4. **Filters binding types:** Forbidden binding types are excluded from the dropdown

### Data Flow

```
Admin Pricing Form (product-pricing.php)
    ↓
Restrictions saved to database (wp_tabesh_settings)
    ↓
PHP builds v2PricingMatrices with restrictions applied
    ↓
Data passed to JavaScript via wp_localize_script
    ↓
Frontend forms show only allowed options
```

### Book Size Selection Order (V2 Mode)

When Pricing Engine V2 is enabled, the correct selection order is:
1. **Book Size** - Must be selected first
2. **Paper Type** - Available types are filtered based on book size
3. **Paper Weight** - Available weights are filtered based on book size AND paper type
4. **Print Type** - May be restricted based on paper type
5. **Binding Type** - Available types are filtered based on book size

This is different from V1 mode where paper types and weights are global and not book-size-specific.

## Testing Recommendations

### Test 1: Admin Pricing Form Toggle Functionality
1. Navigate to the pricing form with `[tabesh_product_pricing]` shortcode
2. Enable Pricing Engine V2 if not already enabled
3. Select a book size
4. For each paper type, toggle the BW and Color switches for different weights
5. Save the settings
6. Verify the restrictions are properly saved to database

### Test 2: Customer Order Form - Restrictions Enforcement
1. Navigate to customer order form with `[tabesh_order_form]` shortcode
2. Ensure V2 is enabled
3. Select a book size
4. Verify only non-forbidden paper types appear in dropdown
5. Select a paper type
6. Verify only allowed weights appear in dropdown
7. Verify pricing calculation works correctly

### Test 3: Admin Order Form - Same Restrictions
1. Navigate to admin order form with `[tabesh_admin_order_form]` shortcode
2. Verify same restrictions apply as in customer form
3. Test that disabled options don't appear
4. Verify pricing calculation respects restrictions

### Test 4: Error Handling
1. With V2 enabled, try to select paper type BEFORE book size
2. Verify error message shows: "لطفاً ابتدا قطع کتاب را انتخاب کنید"
3. Select a book size, then paper type
4. Verify weights populate correctly

### Test 5: V1 Compatibility
1. Disable Pricing Engine V2
2. Verify customer order form still works with V1 pricing
3. Verify paper weights load correctly in V1 mode
4. Verify no restrictions are applied (V1 doesn't support restrictions)

## Security Considerations

All changes maintain WordPress security best practices:
- ✅ Input sanitization using `sanitize_text_field()`, `sanitize_key()`, `intval()`
- ✅ Output escaping in templates (already present)
- ✅ Database queries use `$wpdb->prepare()` with placeholders
- ✅ No new security vulnerabilities introduced
- ✅ Restrictions filtering prevents injection of forbidden options

## Performance Impact

Minimal performance impact:
- Restrictions filtering adds a small overhead when building v2PricingMatrices
- This only happens once per page load when `wp_enqueue_scripts` fires
- The filtered data is cached in JavaScript, no additional API calls needed
- No impact on pricing calculation performance

## Backward Compatibility

- ✅ V1 pricing engine continues to work unchanged
- ✅ Existing orders are not affected
- ✅ Admin can switch between V1 and V2 at any time
- ✅ No database schema changes required
- ✅ No breaking changes to REST API endpoints

## Future Improvements

Potential enhancements for future versions:

1. **Visual Indicators:** Add visual feedback in pricing form showing which paper types/weights have restrictions
2. **Bulk Restrictions:** Allow setting restrictions for multiple book sizes at once
3. **Import/Export:** Add ability to export/import restriction configurations
4. **Restriction Presets:** Common restriction patterns (e.g., "No color printing on books > 200 pages")
5. **Admin Warnings:** Warn admin when creating restrictions that might make certain book sizes unprintable

## Related Documentation

- [PRICING_ENGINE_V2.md](PRICING_ENGINE_V2.md) - Complete V2 pricing engine documentation
- [PRICING_V2_ACTIVATION_FIX.md](PRICING_ENGINE_V2_ACTIVATION_FIX.md) - Previous V2 activation fixes
- [API.md](docs/API.md) - REST API documentation

## Support

For issues related to these fixes:
1. Check browser console for JavaScript errors
2. Enable WP_DEBUG to see detailed error logging
3. Verify V2 pricing matrices are properly configured in database
4. Check that paper types have at least one allowed print type (bw or color)
5. Ensure browser cache is cleared after updates

---

**Version:** 1.0.4  
**Last Updated:** December 2024  
**Author:** Tabesh Development Team
