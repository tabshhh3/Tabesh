# Tabesh Order Form Slider Integration Guide

## Overview

The **Tabesh Order Form Slider** (`[tabesh_order_form_slider]`) is a modern, multi-step animated order form designed specifically for integration with Revolution Slider and other dynamic content systems. It emits real-time JavaScript events on every field change, allowing sliders, preview systems, or custom scripts to respond instantly to user selections.

## Key Features

- ✅ **Modern Multi-Step Design**: 3 streamlined steps with comprehensive fields per step
- ✅ **Real-Time Event System**: Emits `tabesh:formStateChange` events on every field change
- ✅ **Revolution Slider Ready**: Works both inside and outside Revolution Slider
- ✅ **Standalone Functionality**: Fully functional form without requiring slider integration
- ✅ **Mobile-First & Responsive**: Optimized for all screen sizes
- ✅ **RTL Support**: Full Persian language and RTL layout support
- ✅ **Smooth Animations**: Configurable animation speeds
- ✅ **Theme Support**: Light and dark theme variants
- ✅ **Extensible**: Gracefully handles dynamic option additions/removals

## Installation & Prerequisites

### Requirements

1. **WordPress**: 6.8+
2. **PHP**: 8.2.2+
3. **WooCommerce**: Latest version
4. **Tabesh Plugin**: Installed and activated
5. **Pricing Engine V2**: Must be enabled and configured

### Setup Steps

1. **Enable Pricing Engine V2**
   - Go to `WordPress Admin → Tabesh → Product Pricing`
   - Enable "Pricing Engine V2"
   - Configure at least one book size with complete pricing matrix

2. **Configure Product Settings**
   - Go to `WordPress Admin → Tabesh → Settings`
   - Configure book sizes, paper types, binding types, etc.

3. **Test the Form**
   - Create a test page
   - Add shortcode: `[tabesh_order_form_slider]`
   - Verify all fields populate correctly

## Usage

### Basic Shortcode

```
[tabesh_order_form_slider]
```

### With Attributes

```
[tabesh_order_form_slider 
    show_title="yes" 
    redirect_url="/thank-you/" 
    theme="dark"
    animation_speed="fast"]
```

### Shortcode Attributes

| Attribute | Options | Default | Description |
|-----------|---------|---------|-------------|
| `show_title` | `yes`, `no` | `yes` | Show/hide the form header |
| `redirect_url` | Any URL | `/user-orders/` | Redirect URL after order submission |
| `theme` | `light`, `dark` | `light` | Form color theme |
| `animation_speed` | `slow`, `normal`, `fast` | `normal` | Animation transition speed |

## Revolution Slider Integration

### Step 1: Create Your Slider

1. **Create a new Revolution Slider** in WordPress Admin
2. **Design your preview slide** with placeholders for:
   - Book title
   - Book size preview
   - Paper type display
   - Binding type visualization
   - Price display
   - Any other dynamic content

### Step 2: Add the Form to Your Page

Place the form shortcode on the same page as your slider (or on any page):

```
[tabesh_order_form_slider]
```

The form works both **inside** and **outside** the slider container.

### Step 3: Listen for Form Events

Add this JavaScript to your theme's custom JS file or in Revolution Slider's custom JavaScript:

```javascript
// Listen for form state changes
document.addEventListener('tabesh:formStateChange', function(event) {
    const formState = event.detail.state;
    const changedField = event.detail.changed;
    
    console.log('Form updated:', changedField);
    console.log('Current state:', formState);
    
    // Update your slider content based on form state
    updateSliderPreview(formState);
});

function updateSliderPreview(state) {
    // Example: Update book title
    if (state.book_title) {
        jQuery('#slider-book-title').text(state.book_title);
    }
    
    // Example: Update book size preview
    if (state.book_size) {
        jQuery('#slider-book-size').text(state.book_size);
        // You can also change images, animations, etc.
    }
    
    // Example: Update price display
    if (state.calculated_price && state.calculated_price.total_price) {
        const formatted = new Intl.NumberFormat('fa-IR').format(state.calculated_price.total_price);
        jQuery('#slider-price').text(formatted + ' تومان');
    }
}
```

### Step 4: Advanced Integration

For more advanced Revolution Slider integration, you can:

1. **Trigger Slider Animations**:
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const state = event.detail.state;
    const revapi = jQuery('#rev_slider_1').revolution;
    
    if (state.book_size) {
        // Trigger a specific slider action
        revapi.revnext();
    }
});
```

2. **Change Slider Layers Dynamically**:
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const state = event.detail.state;
    
    // Update text layer
    jQuery('.tp-caption.book-title-layer').text(state.book_title);
    
    // Change image layer
    if (state.book_size === 'A5') {
        jQuery('.tp-caption.book-preview-image').attr('src', '/images/a5-preview.png');
    }
});
```

## Event System Reference

### Event Structure

Every field change emits a `tabesh:formStateChange` event with this structure:

```javascript
{
    detail: {
        changed: "field_name",          // Which field changed
        timestamp: "2024-01-15T10:30:00.000Z",  // When it changed
        state: {
            book_title: "My Book",      // string
            book_size: "A5",            // string
            paper_type: "تحریر",         // string
            paper_weight: "80",         // string
            print_type: "bw",           // "bw" or "color"
            page_count: 100,            // number
            quantity: 50,               // number
            binding_type: "شومیز",       // string
            cover_weight: "250",        // string
            extras: ["لب گرد", "خط تا"], // array of strings
            notes: "Special instructions", // string
            calculated_price: {         // object or null
                total_price: 150000,
                // ... other price details
            },
            current_step: 2             // number (1, 2, or 3)
        }
    }
}
```

### Listening for Events

There are multiple ways to listen for events:

**Method 1: Global Document Listener (Recommended for Revolution Slider)**
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const state = event.detail.state;
    // Your code here
});
```

**Method 2: Form Element Listener**
```javascript
jQuery('#tabesh-slider-form').on('tabesh:formStateChange', function(event, data) {
    const state = data.state;
    // Your code here
});
```

**Method 3: Using the API Helper**
```javascript
window.TabeshSliderForm.addEventListener(function(event) {
    const state = event.detail.state;
    // Your code here
});
```

### Removing Event Listeners

```javascript
function myHandler(event) {
    // Handler code
}

// Add listener
document.addEventListener('tabesh:formStateChange', myHandler);

// Remove listener
document.removeEventListener('tabesh:formStateChange', myHandler);
```

### Getting Current State Without Listening

```javascript
// Get current form state at any time
const currentState = window.TabeshSliderForm.getState();
console.log(currentState);
```

## Handling Dynamic Options

The form is designed to handle changes in available options gracefully:

### When Options Are Added
- New options automatically appear in dropdowns/lists
- Existing selections remain valid
- Event data structure remains consistent

### When Options Are Removed
- If a selected option is removed, user must make a new selection
- Form validation prevents submission with invalid options
- Event structure remains the same (just different available values)

### Example: Adding New Extras

When you add a new extra service in the admin:

1. The form automatically includes it in the next load
2. Event listeners don't need updates
3. `formState.extras` array will include the new extra if selected

## Styling & Customization

### Custom CSS

Add custom styles in your theme:

```css
/* Override form colors */
.tabesh-slider-form-container {
    --primary-color: #your-color;
    --secondary-color: #your-color;
}

/* Custom button styles */
.tabesh-slider-form-container .btn-primary {
    background: linear-gradient(135deg, #your-gradient);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .tabesh-slider-form-container {
        padding: 10px;
    }
}
```

### Dark Theme Customization

```css
.tabesh-slider-form-container[data-theme="dark"] {
    background: #your-dark-bg;
}

.tabesh-slider-form-container[data-theme="dark"] .field-label {
    color: #your-light-text;
}
```

## Security Considerations

The form implements all WordPress security best practices:

- ✅ **Nonce Verification**: All AJAX requests include nonces
- ✅ **Input Sanitization**: All user inputs are sanitized
- ✅ **Output Escaping**: All outputs are escaped
- ✅ **Authentication**: Order submission requires logged-in users
- ✅ **Authorization**: Users can only submit their own orders
- ✅ **XSS Prevention**: No unsafe HTML/JS execution
- ✅ **SQL Injection Prevention**: Prepared statements used throughout

## Troubleshooting

### Form Not Appearing

**Issue**: Shortcode shows but form doesn't render

**Solutions**:
1. Check Pricing Engine V2 is enabled
2. Verify at least one book size has complete pricing matrix
3. Check browser console for JavaScript errors
4. Ensure jQuery is loaded

### Events Not Firing

**Issue**: Event listeners not receiving events

**Solutions**:
1. Verify event listener is added after DOM ready
2. Check browser console for errors
3. Ensure form ID is correct: `#tabesh-slider-form`
4. Try the global document listener method

### Options Not Loading

**Issue**: Dropdowns remain empty

**Solutions**:
1. Check pricing matrix configuration
2. Verify REST API is accessible: `/wp-json/tabesh/v1/`
3. Check browser console for AJAX errors
4. Ensure user permissions are correct

### Styling Issues

**Issue**: Form looks broken or styles conflict

**Solutions**:
1. Check for theme CSS conflicts
2. Increase CSS specificity
3. Use browser DevTools to inspect elements
4. Ensure form is not inside conflicting containers

### RTL Layout Issues

**Issue**: Layout appears LTR instead of RTL

**Solutions**:
1. Verify `dir="rtl"` attribute is present
2. Check for parent containers overriding direction
3. Ensure Persian font is loading correctly
4. Use browser DevTools to check computed styles

## Performance Optimization

### For High Traffic Sites

1. **Enable Caching**: Cache the pricing matrix responses
2. **Lazy Load Assets**: Only load when shortcode is present (done automatically)
3. **Minify Assets**: Use a caching plugin to minify CSS/JS
4. **CDN**: Serve assets from a CDN

### For Slider Integration

1. **Debounce Events**: If updating complex visuals:
```javascript
let debounceTimer;
document.addEventListener('tabesh:formStateChange', function(event) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        updateSliderPreview(event.detail.state);
    }, 300); // Wait 300ms after last change
});
```

2. **Update Only Changed Elements**: Check `event.detail.changed` field
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const changed = event.detail.changed;
    const state = event.detail.state;
    
    // Only update relevant elements
    if (changed === 'book_title') {
        jQuery('#slider-title').text(state.book_title);
    }
});
```

## API Reference

### JavaScript API

```javascript
// Global API object
window.TabeshSliderForm = {
    // Get current state
    getState: function() { /* ... */ },
    
    // Add event listener
    addEventListener: function(callback) { /* ... */ },
    
    // Remove event listener
    removeEventListener: function(callback) { /* ... */ }
};
```

### REST API Endpoints

The form uses these Tabesh REST API endpoints:

- `POST /wp-json/tabesh/v1/calculate-price` - Calculate order price
- `POST /wp-json/tabesh/v1/get-allowed-options` - Get filtered options
- `POST /wp-json/tabesh/v1/submit-order` - Submit order (requires auth)

## Examples

### Example 1: Simple Title Display

```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    if (event.detail.changed === 'book_title') {
        jQuery('#my-title-display').text(event.detail.state.book_title);
    }
});
```

### Example 2: Progressive Image Change

```javascript
const bookSizeImages = {
    'A5': '/images/a5.png',
    'A4': '/images/a4.png',
    'وزیری': '/images/vaziri.png'
};

document.addEventListener('tabesh:formStateChange', function(event) {
    if (event.detail.changed === 'book_size') {
        const imageUrl = bookSizeImages[event.detail.state.book_size];
        if (imageUrl) {
            jQuery('#book-preview-image').attr('src', imageUrl);
        }
    }
});
```

### Example 3: Real-Time Price Display

```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    if (event.detail.changed === 'price_calculated') {
        const price = event.detail.state.calculated_price;
        if (price && price.total_price) {
            const formatted = new Intl.NumberFormat('fa-IR').format(price.total_price);
            jQuery('#external-price-display').html(
                `<span class="price-amount">${formatted}</span> تومان`
            );
        }
    }
});
```

### Example 4: Multi-Field Update

```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const state = event.detail.state;
    
    // Update multiple elements
    jQuery('#display-title').text(state.book_title || 'عنوان کتاب');
    jQuery('#display-size').text(state.book_size || '-');
    jQuery('#display-pages').text(state.page_count || '-');
    jQuery('#display-quantity').text(state.quantity || '-');
    
    // Build extras list
    if (state.extras.length > 0) {
        jQuery('#display-extras').html(
            '<ul>' + state.extras.map(e => `<li>${e}</li>`).join('') + '</ul>'
        );
    } else {
        jQuery('#display-extras').text('هیچ خدمت اضافی انتخاب نشده');
    }
});
```

## Support & Resources

- **Documentation**: See plugin README.md
- **API Reference**: See API.md
- **GitHub**: [Repository URL]
- **Support**: Contact plugin developer

## Version History

- **v1.0.0** (2024-01): Initial release with Revolution Slider integration

## License

GPL v2 or later - Same as parent Tabesh plugin

---

**Need Help?** If you encounter any issues not covered in this guide, please check the browser console for error messages and consult the troubleshooting section above.
