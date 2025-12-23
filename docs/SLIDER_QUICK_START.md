# Quick Start: Revolution Slider Integration

## 5-Minute Setup

### Step 1: Add the Shortcode
Place this on your page:
```
[tabesh_order_form_slider]
```

### Step 2: Add Event Listener
Add this JavaScript to your theme or Revolution Slider:

```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    const state = event.detail.state;
    
    // Update your slider elements
    jQuery('#slider-book-title').text(state.book_title);
    jQuery('#slider-book-size').text(state.book_size);
    
    // Update price if calculated
    if (state.calculated_price) {
        const price = new Intl.NumberFormat('fa-IR').format(
            state.calculated_price.total_price
        );
        jQuery('#slider-price').text(price + ' تومان');
    }
});
```

## Event Data Structure

Every field change emits an event with:

```javascript
{
    changed: "field_name",
    timestamp: "2024-01-15T10:30:00.000Z",
    state: {
        book_title: "My Book",
        book_size: "A5",
        paper_type: "تحریر",
        paper_weight: "80",
        print_type: "bw",
        page_count: 100,
        quantity: 50,
        binding_type: "شومیز",
        cover_weight: "250",
        extras: ["لب گرد"],
        notes: "...",
        calculated_price: { total_price: 150000, ... },
        current_step: 2
    }
}
```

## Common Use Cases

### Update Text in Slider
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    if (event.detail.changed === 'book_title') {
        jQuery('#my-text-layer').text(event.detail.state.book_title);
    }
});
```

### Change Images Based on Selection
```javascript
const images = {
    'A5': '/images/a5-preview.png',
    'A4': '/images/a4-preview.png'
};

document.addEventListener('tabesh:formStateChange', function(event) {
    if (event.detail.changed === 'book_size') {
        const size = event.detail.state.book_size;
        jQuery('#preview-image').attr('src', images[size]);
    }
});
```

### Trigger Slider Transitions
```javascript
document.addEventListener('tabesh:formStateChange', function(event) {
    if (event.detail.changed === 'step') {
        // Advance Revolution Slider to next slide
        const revapi = jQuery('#rev_slider_1').revolution;
        revapi.revnext();
    }
});
```

## Shortcode Options

```
[tabesh_order_form_slider 
    show_title="yes"
    theme="light"
    animation_speed="normal"
    redirect_url="/thank-you/"]
```

- `show_title`: Show/hide header (yes/no)
- `theme`: Color theme (light/dark)
- `animation_speed`: Transition speed (slow/normal/fast)
- `redirect_url`: Where to redirect after order

## Troubleshooting

**Events not firing?**
- Check browser console for errors
- Verify form has ID: `#tabesh-slider-form`
- Ensure listener is added after DOM ready

**Form not showing?**
- Verify Pricing Engine V2 is enabled
- Check at least one book size is configured
- Review WordPress admin error messages

For complete documentation, see `docs/ORDER_FORM_SLIDER_GUIDE.md`
