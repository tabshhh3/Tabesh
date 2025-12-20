# Form V2: Print Options and Paper Weight Management Fix

## Summary

This PR fixes three fundamental issues in Order Form V2:

1. ✅ **Display print options based on actual capability of each paper weight**: Now only print options with non-zero prices are shown.

2. ✅ **Fix disabled paper weights reactivating after page refresh**: Database serves as the single source of truth, and weights with zero prices never return to the list.

3. ✅ **Smart auto-disable logic**: If zero price is found during load or save, the option is automatically disabled and hidden.

## Issues Fixed

### Issue 1: Print Options Shown Without Price Validation

**Before:**
```javascript
// JavaScript always showed both options
<input type="radio" name="print_type" value="bw">  // Always enabled
<input type="radio" name="print_type" value="color">  // Always enabled
```

**After:**
```javascript
// JavaScript dynamically enables/disables options
if (!availablePrints.includes('bw')) {
    $bwOption.prop('disabled', true);
}
if (!availablePrints.includes('color')) {
    $colorOption.prop('disabled', true);
}
```

### Issue 2: Disabled Weights Reactivating After Refresh

**Before:**
```php
// All weights added without price validation
foreach ($weights as $weight => $print_types) {
    $allowed_weights[] = array(
        'weight' => $weight,
        'slug' => $this->slugify($paper_type . '-' . $weight),
    );
}
```

**After:**
```php
// Only weights with at least one non-zero price
foreach ($weights as $weight => $print_types) {
    $available_print_types = array();
    foreach ($print_types as $print_type => $price) {
        if (is_numeric($price) && floatval($price) > 0) {
            $available_print_types[] = $print_type;
        }
    }
    
    // Only if at least one print type is available
    if (!empty($available_print_types)) {
        $allowed_weights[] = array(
            'weight' => $weight,
            'available_prints' => $available_print_types,
        );
    }
}
```

### Issue 3: No Price Validation During Load/Save

**After Changes:**
- On load: All weights with zero prices are filtered out
- On weight selection: Only print types with non-zero prices are shown
- Database is single source of truth, frontend never overrides data

## Files Changed

### 1. `includes/handlers/class-tabesh-constraint-manager.php`

**Lines 101-124:** Filter paper weights
```php
// Check if this weight has at least one valid (non-zero) print type
$available_print_types = array();
if (is_array($print_types)) {
    foreach ($print_types as $print_type => $price) {
        if (is_numeric($price) && floatval($price) > 0) {
            $available_print_types[] = $print_type;
        }
    }
}

// Only add this weight if it has at least one available print type
if (!empty($available_print_types)) {
    $allowed_weights[] = array(
        'weight' => $weight,
        'slug' => $this->slugify($paper_type . '-' . $weight),
        'available_prints' => $available_print_types,  // New
    );
}
```

**Lines 91-93:** Add `$selected_paper_weight` variable
```php
$selected_paper_type = $current_selection['paper_type'] ?? null;
$selected_paper_weight = $current_selection['paper_weight'] ?? null;  // New
$selected_binding_type = $current_selection['binding_type'] ?? null;
```

**Lines 160-200:** Check selected weight to determine allowed print types
```php
// If a specific weight is selected, check which print types are available for that weight
if ($selected_paper_weight && isset($page_costs[$selected_paper_type][$selected_paper_weight])) {
    $weight_print_types = $page_costs[$selected_paper_type][$selected_paper_weight];
    
    // Only include print types that:
    // 1. Are not forbidden by restrictions
    // 2. Have non-zero prices for this specific weight
    foreach ($all_print_types as $print_type) {
        $price = $weight_print_types[$print_type] ?? 0;
        if (is_numeric($price) && floatval($price) > 0) {
            $result['allowed_print_types'][] = array(
                'type' => $print_type,
                'slug' => $print_type,
                'label' => 'bw' === $print_type ? __('Black & White', 'tabesh') : __('Color', 'tabesh'),
            );
        }
    }
}
```

### 2. `assets/js/order-form-v2.js`

**Lines 334-355:** Store `available_prints` with each option
```javascript
function loadPaperWeights(paperType) {
    // ...
    weights.forEach(function(weightInfo) {
        const $option = $('<option></option>')
            .val(weightInfo.weight)
            .text(weightInfo.weight + ' gsm')
            .data('available_prints', weightInfo.available_prints || []);  // New
        $weightSelect.append($option);
    });
}
```

**Lines 357-435:** Dynamic enable/disable logic
```javascript
function loadPrintTypes() {
    // Get available_prints info from selected option
    const availablePrints = selectedOption.data('available_prints') || [];
    
    // Disable options that are not available (price = 0)
    if (!availablePrints.includes('bw')) {
        $bwOption.prop('disabled', true).prop('checked', false);
        $bwCard.addClass('disabled');
    }
    if (!availablePrints.includes('color')) {
        $colorOption.prop('disabled', true).prop('checked', false);
        $colorCard.addClass('disabled');
    }
    
    // Auto-select if only one option is available
    if (availablePrints.length === 1) {
        // ...
    }
}
```

### 3. `assets/css/order-form-v2.css`

**Lines 401-420:** Styles for disabled options
```css
/* Disabled print option style - when price is 0 or not available */
.print-option.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.print-option.disabled .print-card {
    background: var(--wizard-disabled-bg, #f5f5f5);
    border-color: var(--wizard-border);
}

.print-option.disabled:hover .print-card {
    border-color: var(--wizard-border);
    background: var(--wizard-disabled-bg, #f5f5f5);
    transform: none;
    box-shadow: none;
}
```

## Testing

### 1. Unit Tests

```bash
php test-paper-weight-filtering.php
```

**Expected Results:**
```
Test 1: Both print types available - PASS
Test 2: Only one print type available (bw) - PASS
Test 3: All print types have zero price - PASS
Test 4: Complete paper type filtering - PASS
```

### 2. Manual Testing

**Scenario 1: Weight with both print types**
1. In pricing admin panel, set both bw and color prices to non-zero for a weight
2. Open order form
3. Select that paper type and weight
4. **Expected:** Both "Black & White" and "Color" options are enabled

**Scenario 2: Weight with one print type**
1. For a weight, set bw price to non-zero and color price to zero
2. Open order form
3. Select that weight
4. **Expected:** Only "Black & White" is enabled, "Color" is disabled and grayed out

**Scenario 3: Weight with all zero prices**
1. For a weight, set both prices to zero
2. Refresh the page
3. **Expected:** That weight is not shown in the list at all

**Scenario 4: Page Refresh**
1. In admin panel, disable a weight (price = 0)
2. Open order form
3. Refresh the page
4. **Expected:** Disabled weight remains absent from the list

## New API Behavior

### Endpoint: `/get-allowed-options`

**Request:**
```json
{
  "book_size": "A5",
  "current_selection": {
    "paper_type": "Tahrir",
    "paper_weight": "70"
  }
}
```

**Response (Before):**
```json
{
  "allowed_papers": [{
    "type": "Tahrir",
    "weights": [
      {"weight": "60"},
      {"weight": "70"},  // No available_prints info
      {"weight": "80"}
    ]
  }],
  "allowed_print_types": [
    {"type": "bw"},
    {"type": "color"}  // All types without filtering
  ]
}
```

**Response (After):**
```json
{
  "allowed_papers": [{
    "type": "Tahrir",
    "weights": [
      {"weight": "60", "available_prints": ["bw", "color"]},
      {"weight": "80", "available_prints": ["bw"]}
      // Weight 70 removed (price = 0)
    ]
  }],
  "allowed_print_types": [
    {"type": "bw"}
    // Only bw for weight 80
  ]
}
```

## Security Notes

- ✅ All inputs are sanitized (`sanitize_text_field`, `floatval`)
- ✅ Type checking before use (`is_numeric`, `is_array`)
- ✅ Strict comparison with `===`
- ✅ Proper validation on both server and client sides

## Backward Compatibility

- ✅ **Backward Compatible**: Changes only add new filtering
- ✅ **No Breaking Changes**: Previous API structure is preserved
- ✅ **Database**: No migration required
- ✅ **Settings**: Previous settings work without changes

## Requirements

- PHP 8.2.2+
- WordPress 6.8+
- Tabesh Plugin with Pricing Engine V2 enabled

## Conclusion

These changes ensure:

1. **Single Source of Truth:** Database is the only source of truth
2. **Better UI/UX:** Users only see valid options
3. **Error Reduction:** Invalid options are never displayed
4. **Performance:** Filtering happens on the server side
5. **Maintainability:** Cleaner, more maintainable code

## Related Links

- [PRICING_ENGINE_V2.md](PRICING_ENGINE_V2.md)
- [DEPENDENCY_ENGINE_V2_GUIDE.md](DEPENDENCY_ENGINE_V2_GUIDE.md)
- [ORDER_FORM_V2_GUIDE.md](ORDER_FORM_V2_GUIDE.md)
