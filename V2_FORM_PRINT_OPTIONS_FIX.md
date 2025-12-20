# رفع اصولی مشکلات فرم V2: گزینه‌های چاپ و گرماژ کاغذ

## خلاصه تغییرات

این PR سه مشکل اساسی در فرم سفارش V2 را برطرف می‌کند:

1. ✅ **نمایش گزینه چاپ بر اساس قابلیت واقعی هر گرماژ**: اکنون فقط گزینه‌های چاپی که برای آن گرماژ قیمت غیر صفر دارند، نمایش داده می‌شوند.

2. ✅ **رفع مشکل فعال شدن مجدد گرماژهای غیرفعال**: دیتابیس به عنوان منبع واحد حقیقت عمل می‌کند و گرماژهایی که قیمت صفر دارند، هرگز به لیست برنمی‌گردند.

3. ✅ **منطق هوشمند غیرفعال‌سازی خودکار**: اگر قیمت صفر یافت شود، گزینه به‌طور خودکار غیرفعال و از نمایش حذف می‌شود.

## مشکلات برطرف شده

### مشکل ۱: نمایش گزینه چاپ بدون توجه به قیمت

**قبل از تغییرات:**
```javascript
// JavaScript همیشه هر دو گزینه را نمایش می‌داد
<input type="radio" name="print_type" value="bw">  // همیشه فعال
<input type="radio" name="print_type" value="color">  // همیشه فعال
```

**بعد از تغییرات:**
```javascript
// JavaScript به‌صورت پویا گزینه‌ها را فعال/غیرفعال می‌کند
if (!availablePrints.includes('bw')) {
    $bwOption.prop('disabled', true);
}
if (!availablePrints.includes('color')) {
    $colorOption.prop('disabled', true);
}
```

### مشکل ۲: فعال شدن مجدد گرماژهای غیرفعال پس از refresh

**قبل از تغییرات:**
```php
// تمام گرماژها بدون بررسی قیمت اضافه می‌شدند
foreach ($weights as $weight => $print_types) {
    $allowed_weights[] = array(
        'weight' => $weight,
        'slug' => $this->slugify($paper_type . '-' . $weight),
    );
}
```

**بعد از تغییرات:**
```php
// فقط گرماژهایی با حداقل یک قیمت غیر صفر
foreach ($weights as $weight => $print_types) {
    $available_print_types = array();
    foreach ($print_types as $print_type => $price) {
        if (is_numeric($price) && floatval($price) > 0) {
            $available_print_types[] = $print_type;
        }
    }
    
    // فقط اگر حداقل یک نوع چاپ موجود باشد
    if (!empty($available_print_types)) {
        $allowed_weights[] = array(
            'weight' => $weight,
            'available_prints' => $available_print_types,
        );
    }
}
```

### مشکل ۳: عدم بررسی قیمت در زمان load و save

**بعد از تغییرات:**
- هنگام load: تمام گرماژها با قیمت صفر فیلتر می‌شوند
- هنگام انتخاب گرماژ: فقط نوع‌های چاپ با قیمت غیر صفر نمایش داده می‌شوند
- دیتابیس منبع واحد حقیقت است و frontend هیچ‌گاه داده را override نمی‌کند

## فایل‌های تغییر یافته

### ۱. `includes/handlers/class-tabesh-constraint-manager.php`

**خطوط ۱۰۱-۱۲۴:** فیلتر کردن گرماژهای کاغذ
```php
// بررسی اینکه آیا این گرماژ حداقل یک نوع چاپ معتبر (قیمت غیر صفر) دارد
$available_print_types = array();
if (is_array($print_types)) {
    foreach ($print_types as $print_type => $price) {
        if (is_numeric($price) && floatval($price) > 0) {
            $available_print_types[] = $print_type;
        }
    }
}

// فقط اگر حداقل یک نوع چاپ موجود باشد، این گرماژ را اضافه کن
if (!empty($available_print_types)) {
    $allowed_weights[] = array(
        'weight' => $weight,
        'slug' => $this->slugify($paper_type . '-' . $weight),
        'available_prints' => $available_print_types,  // جدید
    );
}
```

**خطوط ۹۱-۹۳:** اضافه کردن متغیر `$selected_paper_weight`
```php
$selected_paper_type = $current_selection['paper_type'] ?? null;
$selected_paper_weight = $current_selection['paper_weight'] ?? null;  // جدید
$selected_binding_type = $current_selection['binding_type'] ?? null;
```

**خطوط ۱۶۰-۲۰۰:** بررسی گرماژ انتخابی برای تعیین نوع‌های چاپ مجاز
```php
// اگر گرماژ خاصی انتخاب شده، بررسی کن که کدام نوع‌های چاپ برای آن موجود هستند
if ($selected_paper_weight && isset($page_costs[$selected_paper_type][$selected_paper_weight])) {
    $weight_print_types = $page_costs[$selected_paper_type][$selected_paper_weight];
    
    // فقط نوع‌های چاپی را شامل شو که:
    // ۱. توسط محدودیت‌ها ممنوع نشده‌اند
    // ۲. برای این گرماژ خاص قیمت غیر صفر دارند
    foreach ($all_print_types as $print_type) {
        $price = $weight_print_types[$print_type] ?? 0;
        if (is_numeric($price) && floatval($price) > 0) {
            $result['allowed_print_types'][] = array(
                'type' => $print_type,
                'slug' => $print_type,
                'label' => 'bw' === $print_type ? __('سیاه و سفید', 'tabesh') : __('رنگی', 'tabesh'),
            );
        }
    }
}
```

### ۲. `assets/js/order-form-v2.js`

**خطوط ۳۳۴-۳۵۵:** ذخیره `available_prints` با هر گزینه
```javascript
function loadPaperWeights(paperType) {
    // ...
    weights.forEach(function(weightInfo) {
        const $option = $('<option></option>')
            .val(weightInfo.weight)
            .text(weightInfo.weight + ' گرم')
            .data('available_prints', weightInfo.available_prints || []);  // جدید
        $weightSelect.append($option);
    });
}
```

**خطوط ۳۵۷-۴۳۵:** منطق فعال/غیرفعال کردن پویا
```javascript
function loadPrintTypes() {
    // دریافت اطلاعات available_prints از گزینه انتخابی
    const availablePrints = selectedOption.data('available_prints') || [];
    
    // غیرفعال کردن گزینه‌هایی که موجود نیستند (قیمت = ۰)
    if (!availablePrints.includes('bw')) {
        $bwOption.prop('disabled', true).prop('checked', false);
        $bwCard.addClass('disabled');
    }
    if (!availablePrints.includes('color')) {
        $colorOption.prop('disabled', true).prop('checked', false);
        $colorCard.addClass('disabled');
    }
    
    // انتخاب خودکار اگر فقط یک گزینه موجود باشد
    if (availablePrints.length === 1) {
        // ...
    }
}
```

### ۳. `assets/css/order-form-v2.css`

**خطوط ۴۰۱-۴۲۰:** استایل برای گزینه‌های غیرفعال
```css
/* استایل گزینه چاپ غیرفعال - زمانی که قیمت ۰ یا در دسترس نیست */
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

## نحوه تست

### ۱. تست واحد (Unit Test)

```bash
php test-paper-weight-filtering.php
```

**نتایج مورد انتظار:**
```
Test 1: Both print types available - PASS
Test 2: Only one print type available (bw) - PASS
Test 3: All print types have zero price - PASS
Test 4: Complete paper type filtering - PASS
```

### ۲. تست دستی

**سناریو ۱: گرماژ با هر دو نوع چاپ**
1. در پنل مدیریت قیمت‌گذاری، برای یک گرماژ هر دو قیمت bw و color را غیر صفر تنظیم کنید
2. فرم سفارش را باز کنید
3. آن نوع کاغذ و گرماژ را انتخاب کنید
4. **انتظار:** هر دو گزینه "سیاه و سفید" و "رنگی" فعال باشند

**سناریو ۲: گرماژ با یک نوع چاپ**
1. برای یک گرماژ، قیمت bw را غیر صفر و قیمت color را صفر تنظیم کنید
2. فرم سفارش را باز کنید
3. آن گرماژ را انتخاب کنید
4. **انتظار:** فقط "سیاه و سفید" فعال باشد، "رنگی" غیرفعال و خاکستری شود

**سناریو ۳: گرماژ با هر دو قیمت صفر**
1. برای یک گرماژ، هر دو قیمت را صفر تنظیم کنید
2. صفحه را refresh کنید
3. **انتظار:** آن گرماژ اصلاً در لیست نمایش داده نشود

**سناریو ۴: Refresh صفحه**
1. در پنل مدیریت، یک گرماژ را غیرفعال کنید (قیمت = ۰)
2. فرم سفارش را باز کنید
3. صفحه را refresh کنید
4. **انتظار:** گرماژ غیرفعال همچنان در لیست نباشد

## رفتار جدید API

### Endpoint: `/get-allowed-options`

**Request:**
```json
{
  "book_size": "A5",
  "current_selection": {
    "paper_type": "تحریر",
    "paper_weight": "70"
  }
}
```

**Response (قبل):**
```json
{
  "allowed_papers": [{
    "type": "تحریر",
    "weights": [
      {"weight": "60"},
      {"weight": "70"},  // بدون اطلاعات available_prints
      {"weight": "80"}
    ]
  }],
  "allowed_print_types": [
    {"type": "bw"},
    {"type": "color"}  // همه نوع‌ها بدون فیلتر
  ]
}
```

**Response (بعد):**
```json
{
  "allowed_papers": [{
    "type": "تحریر",
    "weights": [
      {"weight": "60", "available_prints": ["bw", "color"]},
      {"weight": "80", "available_prints": ["bw"]}
      // گرماژ ۷۰ حذف شده (قیمت ۰)
    ]
  }],
  "allowed_print_types": [
    {"type": "bw"}
    // فقط bw برای گرماژ ۸۰
  ]
}
```

## نکات امنیتی

- ✅ تمام input‌ها sanitize می‌شوند (`sanitize_text_field`, `floatval`)
- ✅ بررسی نوع داده قبل از استفاده (`is_numeric`, `is_array`)
- ✅ استفاده از `===` برای مقایسه دقیق
- ✅ Validation مناسب در سمت سرور و کلاینت

## سازگاری با نسخه‌های قبل

- ✅ **Backward Compatible**: تغییرات فقط اضافه کردن فیلتر جدید هستند
- ✅ **No Breaking Changes**: ساختار قبلی API حفظ شده است
- ✅ **Database**: نیازی به migration ندارد
- ✅ **Settings**: تنظیمات قبلی بدون تغییر کار می‌کنند

## الزامات

- PHP 8.2.2+
- WordPress 6.8+
- Plugin Tabesh با Pricing Engine V2 فعال

## نتیجه‌گیری

این تغییرات اطمینان می‌دهند که:

1. **منبع حقیقت:** دیتابیس تنها منبع حقیقت است
2. **UI/UX بهتر:** کاربر فقط گزینه‌های معتبر را می‌بیند
3. **کاهش خطا:** گزینه‌های نامعتبر اصلاً نمایش داده نمی‌شوند
4. **Performance:** فیلتر در سمت سرور انجام می‌شود
5. **Maintainability:** کد تمیزتر و قابل نگهداری‌تر

## لینک‌های مرتبط

- [PRICING_ENGINE_V2.md](PRICING_ENGINE_V2.md)
- [DEPENDENCY_ENGINE_V2_GUIDE.md](DEPENDENCY_ENGINE_V2_GUIDE.md)
- [ORDER_FORM_V2_GUIDE.md](ORDER_FORM_V2_GUIDE.md)
