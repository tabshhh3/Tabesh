# AI Browser Enhancement Implementation Summary

## Overview

This PR implements comprehensive enhancements to the Tabesh AI Browser system, adding 7 major features including page content analysis, automatic field explanations, user persona building, site indexing, improved responsive sidebar, instant highlighting, and rich Gemini AI integration.

## Features Implemented

### 1. Page Analyzer (تحلیلگر صفحه) ✅

**Files Created:**
- `includes/ai/class-tabesh-ai-page-analyzer.php`
- `assets/js/ai-page-analyzer.js`

**Features:**
- Extracts and sanitizes full DOM content including forms, buttons, links, and navigation
- Detects hovered elements with debounced tracking
- Identifies page type (order-form, cart, checkout, product, homepage, etc.)
- Builds enriched context for Gemini AI
- Summarizes form interactions and filled fields

**Security:**
- All HTML content sanitized with `wp_strip_all_tags()`
- URLs validated with `esc_url_raw()`
- Form data sanitized with appropriate WordPress functions
- Content length limited to prevent DoS

### 2. Field Explainer (توضیح‌دهنده خودکار فیلدها) ✅

**Files Created:**
- `includes/ai/class-tabesh-ai-field-explainer.php`
- `assets/js/ai-field-explainer.js`
- `assets/css/ai-instant-highlight.css` (styles)

**Features:**
- Automatically explains fields when user focuses or changes values
- Predefined explanations for common fields (paper types, binding, book sizes)
- AI-powered explanations via Gemini for complex fields
- Beautiful animated tooltips with icons
- Debounced event handling (500ms focus, 800ms change)
- 5-minute transient cache for repeated explanations
- Tracks which fields already explained to avoid spam

**User Experience:**
- Persian paper type explanations (گلاسه, تحریر, فانتزی)
- Binding type guidance (گالینگور, سلفون, لمینت, فنر)
- Book size descriptions (رقعی, وزیری, رحلی, پالتویی)
- Smooth fade-in animations
- Auto-dismissal after 5 seconds

### 3. Persona Builder (سازنده شخصیت کاربر) ✅

**Files Created:**
- `includes/ai/class-tabesh-ai-persona-builder.php`

**Features:**
- Analyzes user behavior to detect profession (author, publisher, printer, buyer)
- Determines experience level (beginner, intermediate, expert)
- Identifies current intent (ordering_book, browsing_catalog, seeking_help, etc.)
- Extracts user interests from browsing patterns
- Detects confusion signals (idle time, rapid clicks, form abandonment)
- Calculates engagement level
- Summarizes browsing history and form interactions

**Persona Structure:**
```php
[
    'detected_profession'  => 'author|publisher|printer|buyer',
    'experience_level'     => 'beginner|intermediate|expert',
    'current_intent'       => 'ordering_book|browsing_catalog|...',
    'interests'            => ['literature', 'educational', ...],
    'browsing_history'     => [url => visit_count],
    'form_interactions'    => [field_name => interaction_count],
    'confusion_signals'    => [array of signals],
    'engagement_level'     => 'low|medium|high',
    'preferred_content'    => ['prefers_images' => bool, ...]
]
```

### 4. Site Indexer (ایندکسر صفحات) ✅

**Files Created:**
- `includes/ai/class-tabesh-ai-site-indexer.php`

**Database Table:**
```sql
CREATE TABLE wp_tabesh_ai_site_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL UNIQUE,
    page_title VARCHAR(255),
    page_content_summary TEXT,
    page_keywords JSON,
    page_type VARCHAR(50),
    last_scanned DATETIME,
    created_at DATETIME
);
```

**Features:**
- Scans pages from WordPress/Yoast sitemap XML
- Extracts title, content summary (500 chars), and keywords
- Auto-detects page type based on URL and content
- Keyword extraction (top 20 words, excluding Persian stop words)
- Search functionality across title, summary, and keywords
- Daily cron job for automatic re-indexing
- Cleanup old pages after 90 days

**Cron Job:**
- Hook: `tabesh_ai_index_site_pages`
- Frequency: Daily
- Auto-scheduled on plugin activation
- Removed on deactivation

### 5. Responsive Sidebar (Sidebar واکنشگرا) ✅

**Files Modified:**
- `assets/css/ai-browser.css`

**Desktop (≥769px):**
- Sidebar opens from right (400px width)
- Content pushes left with smooth `margin-left` transition
- Both visible simultaneously - NO overlay
- No interference between sidebar and content

**Tablet (769px - 1024px):**
- Sidebar width: 380px
- Same push-content behavior

**Mobile (<768px):**
- Sidebar opens from bottom
- Height: 70vh
- Rounded top corners (24px radius)
- Blur backdrop on overlay
- Body overflow hidden when open

**Large Desktop (≥1440px):**
- Sidebar width: 450px
- Enhanced spacing

### 6. Instant Highlight (هایلایت فوری) ✅

**Files Created/Modified:**
- `assets/css/ai-instant-highlight.css`
- `assets/js/ai-tour-guide.js` (added `instantHighlight()` function)

**Features:**
- Scroll to element with smooth behavior
- Animated pulse ring effect with multiple layers
- Directional arrow pointer (top/bottom/left/right)
- Animated tooltip with message
- Optional spotlight rotating effect
- Red flag mode for urgent/confused user guidance
- Auto-removal after customizable duration (default 5s)
- Accessibility: Respects `prefers-reduced-motion`

**Usage:**
```javascript
window.tabeshAITourGuide.instantHighlight(
    '#cart-button',
    'سبد خرید اینجاست!',
    {
        arrow: 'top',
        redFlag: true,
        spotlight: true,
        duration: 5000
    }
);
```

### 7. Gemini AI Integration (ادغام با Gemini) ✅

**Files Modified:**
- `includes/ai/class-tabesh-ai-gemini.php`
- `includes/ai/class-tabesh-ai-browser.php`

**Enhanced Context:**
- User persona automatically attached to every request
- Page context (title, content, forms) sent when available
- User profile data integrated
- Rich system prompts with contextual information

**Example Context Structure:**
```php
$context = [
    'user_id' => 123,
    'guest_uuid' => 'abc-123',
    'page_context' => [
        'page_title' => 'فرم سفارش',
        'page_type' => 'order-form',
        'forms' => [...],
        'hovered_element' => [...]
    ],
    'user_profile' => [
        'profession' => 'author',
        'interests' => ['literature']
    ]
];
```

## REST API Endpoints

All endpoints use `TABESH_REST_NAMESPACE` = `/wp-json/tabesh/v1/`

### New Endpoints:

1. **POST `/ai/page/analyze`**
   - Analyzes client-side page data
   - Returns sanitized context and Gemini-ready summary
   - Permission: Public

2. **POST `/ai/field/explain`**
   - Gets explanation for a form field
   - Uses predefined or AI-generated explanations
   - Caches results for 5 minutes
   - Permission: Public

3. **POST `/ai/persona/build`**
   - Builds user persona from behavior history
   - Returns full persona and summary
   - Permission: Public

### Modified Endpoints:

4. **POST `/ai/browser/track`** (enhanced)
   - Now supports richer event data

## Security Measures

✅ **Input Sanitization:**
- All DOM content: `wp_strip_all_tags()`
- URLs: `esc_url_raw()`
- Text fields: `sanitize_text_field()`
- Textarea fields: `sanitize_textarea_field()`
- Email: `sanitize_email()`
- Numbers: `absint()` / `intval()` / `floatval()`

✅ **Output Escaping:**
- HTML: `esc_html()`
- Attributes: `esc_attr()`
- URLs: `esc_url()`
- JavaScript: Escaping functions in all JS files

✅ **Nonces:**
- All AJAX requests include `X-WP-Nonce` header
- Verified on server side with `wp_create_nonce('wp_rest')`

✅ **Database:**
- All queries use `$wpdb->prepare()` with placeholders
- Proper phpcs comments for false positives

✅ **WordPress Coding Standards:**
- All PHP files pass `phpcs` with WordPress rules
- Auto-fixed with `phpcbf`
- Only 6 acceptable warnings remaining (caching notices)

## Performance Optimizations

✅ **Debouncing:**
- Hover events: 500ms
- Field focus: 500ms
- Field change: 800ms
- History save: 5000ms

✅ **Caching:**
- Field explanations: 5 minutes (transient)
- Persona summary: In-memory cache
- Chat history: localStorage + periodic server sync

✅ **Lazy Loading:**
- Scripts loaded in footer
- Proper dependency management
- Conditional enqueuing based on settings

✅ **Batch Processing:**
- Site indexing: 0.1s delay between pages
- Cleanup: Bulk delete for old records

## Code Quality

- ✅ WordPress Coding Standards compliant
- ✅ PHPDoc comments on all functions
- ✅ RTL support throughout
- ✅ Accessibility features (keyboard navigation, reduced motion)
- ✅ Error handling with WP_Error
- ✅ Proper namespacing (Tabesh_ prefix)
- ✅ Autoloading compatible

## Database Changes

**New Table:**
- `wp_tabesh_ai_site_pages` - Indexed site pages

**Modified Tables:**
- None (all AI tables already existed)

## Configuration Options

**New Options:**
- `tabesh_ai_field_explainer_enabled` (default: true)
- `tabesh_ai_tracking_enabled` (already existed)
- `tabesh_ai_browser_enabled` (already existed)

## Testing Checklist

### Completed:
- ✅ PHP linting (phpcs)
- ✅ Code style auto-fixes (phpcbf)
- ✅ Security review (sanitization, escaping, nonces)
- ✅ Database query validation

### Remaining:
- ⏳ Browser testing (Chrome, Firefox, Safari)
- ⏳ Mobile responsive testing
- ⏳ RTL layout testing
- ⏳ Gemini API integration testing
- ⏳ UI screenshots

## Migration Notes

**Automatic on Plugin Activation:**
- New database table created via `dbDelta()`
- Cron job scheduled
- No manual migration needed

**Deactivation:**
- Cron job unscheduled
- Database tables preserved (WordPress best practice)

## File Structure

```
Tabesh/
├── includes/ai/
│   ├── class-tabesh-ai-page-analyzer.php      [NEW]
│   ├── class-tabesh-ai-field-explainer.php    [NEW]
│   ├── class-tabesh-ai-persona-builder.php    [NEW]
│   ├── class-tabesh-ai-site-indexer.php       [NEW]
│   ├── class-tabesh-ai-browser.php            [MODIFIED]
│   ├── class-tabesh-ai-gemini.php             [MODIFIED]
│   └── ...
├── assets/
│   ├── js/
│   │   ├── ai-page-analyzer.js                [NEW]
│   │   ├── ai-field-explainer.js              [NEW]
│   │   ├── ai-tour-guide.js                   [MODIFIED]
│   │   └── ...
│   └── css/
│       ├── ai-instant-highlight.css           [NEW]
│       ├── ai-browser.css                     [MODIFIED]
│       └── ...
└── tabesh.php                                 [MODIFIED]
```

## Known Limitations

1. **Site Indexer:**
   - Requires sitemap.xml (WordPress core or Yoast SEO)
   - 0.1s delay between pages (intentional to avoid server overload)
   - Limited to 500-char content summary

2. **Field Explainer:**
   - Predefined explanations only in Persian
   - AI explanations depend on Gemini API availability
   - Cache duration is 5 minutes (not configurable yet)

3. **Persona Builder:**
   - Requires behavior tracking to be enabled
   - Accuracy improves with more interactions
   - No manual persona override yet

4. **Instant Highlight:**
   - Requires element to be in DOM
   - Fixed 5-second duration (not configurable via UI)

## Future Enhancements (Phase 8)

- [ ] Admin settings page for AI features
- [ ] Configurable field explanation messages
- [ ] Manual persona override
- [ ] Indexing schedule configuration
- [ ] Instant highlight duration setting
- [ ] Custom predefined explanations manager

## Breaking Changes

**None.** This is a purely additive PR. All new features are:
- Backward compatible
- Opt-in via settings (default enabled)
- Non-breaking for existing functionality

## Upgrade Path

1. Deactivate plugin
2. Update files
3. Reactivate plugin
4. New database table auto-created
5. Cron job auto-scheduled
6. No manual intervention needed

## Support

For issues or questions:
- Check existing behavior logs in `wp_tabesh_ai_behavior_logs`
- Review indexed pages in `wp_tabesh_ai_site_pages`
- Enable WordPress debug logging: `define('WP_DEBUG_LOG', true);`
- Check REST API endpoints with tools like Postman

## Credits

Developed by: GitHub Copilot
Plugin: Tabesh (Chapco)
Framework: WordPress 6.8+, PHP 8.2.2+
AI: Google Gemini 2.0 Flash

---

**Total Lines Added:** ~2,800
**Total Files Modified:** 5
**Total Files Created:** 7
**Code Quality Score:** ✅ Passes WordPress Coding Standards
