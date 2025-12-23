# New Feature: Order Form Slider Integration

## Overview

A new modern order form shortcode has been added to support real-time integration with Revolution Slider and other dynamic content systems.

## Shortcode

```
[tabesh_order_form_slider]
```

## Key Features

- **Modern 3-Step Design**: Streamlined form with comprehensive fields
- **Real-Time Events**: Emits `tabesh:formStateChange` on every field change
- **Revolution Slider Ready**: Works both inside and outside slider
- **Standalone**: Fully functional without slider integration
- **Mobile-First**: Responsive design for all screen sizes
- **RTL Support**: Persian language and RTL layout
- **Smooth Animations**: Configurable animation speeds
- **Secure**: Full WordPress security best practices

## Quick Example

### 1. Add Shortcode to Page
```
[tabesh_order_form_slider]
```

### 2. Listen for Events (JavaScript)
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const state = event.detail.state;
    
    // Update your content based on form state
    jQuery('#book-title-display').text(state.book_title);
    jQuery('#book-size-display').text(state.book_size);
    
    if (state.calculated_price) {
        jQuery('#price-display').text(
            new Intl.NumberFormat('fa-IR').format(
                state.calculated_price.total_price
            ) + ' تومان'
        );
    }
});
```

## Event Structure

Every field change emits a complete state snapshot:

```javascript
{
    changed: "field_name",           // Which field changed
    timestamp: "ISO-8601-timestamp", // When it changed
    state: {
        book_title: "...",
        book_size: "...",
        paper_type: "...",
        paper_weight: "...",
        print_type: "bw" or "color",
        page_count: 100,
        quantity: 50,
        binding_type: "...",
        cover_weight: "...",
        extras: ["...", "..."],
        notes: "...",
        calculated_price: {...} or null,
        current_step: 1, 2, or 3
    }
}
```

## Revolution Slider Integration

The form is specifically designed to work with Revolution Slider:

1. **Place form anywhere** on the same page as your slider
2. **Add event listeners** to update slider layers
3. **Form emits events** on every change for real-time updates

Example:
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    // Update Revolution Slider text layer
    jQuery('.tp-caption.book-title').text(event.detail.state.book_title);
    
    // Change slider image based on book size
    if (event.detail.state.book_size === 'A5') {
        jQuery('.tp-caption.book-image').attr('src', '/images/a5.png');
    }
});
```

## Documentation

- **Full Guide**: `docs/ORDER_FORM_SLIDER_GUIDE.md`
- **Quick Start**: `docs/SLIDER_QUICK_START.md`

## Files Added

- `includes/handlers/class-tabesh-order-form-slider.php`
- `templates/frontend/order-form-slider.php`
- `assets/js/order-form-slider.js`
- `assets/css/order-form-slider.css`
- `docs/ORDER_FORM_SLIDER_GUIDE.md`
- `docs/SLIDER_QUICK_START.md`

## Backward Compatibility

This is a **new feature** that does not modify any existing functionality:

- ✅ `[tabesh_order_form_v2]` remains unchanged
- ✅ All existing forms continue to work
- ✅ No breaking changes
- ✅ Purely additive feature

## Requirements

- WordPress 6.8+
- PHP 8.2.2+
- WooCommerce (latest)
- Tabesh Plugin active
- Pricing Engine V2 enabled and configured

## Usage

See comprehensive documentation in `docs/ORDER_FORM_SLIDER_GUIDE.md` for:

- Detailed setup instructions
- Revolution Slider integration examples
- Event system reference
- Troubleshooting guide
- Security considerations
- Performance optimization

## Quick Start

1. Enable Pricing Engine V2 in Tabesh settings
2. Configure at least one book size with pricing matrix
3. Add shortcode to a page: `[tabesh_order_form_slider]`
4. Add JavaScript event listeners to respond to form changes
5. Test and customize as needed

## Support

For questions or issues with this feature, see:
- Documentation: `docs/ORDER_FORM_SLIDER_GUIDE.md`
- Quick Start: `docs/SLIDER_QUICK_START.md`
- Main Plugin: `README.md`
