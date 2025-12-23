# Cover Weight Cascade Fix - Admin Order Form

## مشکل / Problem

در فرم ثبت سفارش ادمین (`[tabesh_admin_order_form]`)، گزینه‌های گرماژ جلد (Cover Weight) بر اساس نوع صحافی (Binding Type) انتخاب شده فیلتر نمی‌شدند. این در حالی است که در فرم سفارش v2 این قابلیت به درستی کار می‌کند.

In the admin order form (`[tabesh_admin_order_form]` shortcode), cover weight options were not filtered based on the selected binding type, although this feature worked correctly in the v2 order form.

## راه‌حل / Solution

### تغییرات اعمال شده / Changes Made

#### 1. افزودن تابع `updateCoverWeights()` / Added `updateCoverWeights()` function

تابع جدیدی در `assets/js/admin-order-form.js` اضافه شد که گزینه‌های گرماژ جلد را بر اساس نوع صحافی انتخاب شده فیلتر می‌کند:

```javascript
function updateCoverWeights() {
    const $bindingSelect = $('#aof-binding-type');
    const $coverSelect = $('#aof-cover-paper-weight');
    const selectedOption = $bindingSelect.find('option:selected');
    const coverWeights = selectedOption.data('cover_weights');

    // Clear current options and add default empty option
    $coverSelect.empty();
    $coverSelect.append(
        $('<option></option>')
            .val('')
            .text(tabeshAdminOrderForm.strings.selectOption)
    );

    // Validate coverWeights is an array
    if (!Array.isArray(coverWeights) || coverWeights.length === 0) {
        // No cover weights available for this binding type - default option already added
        return;
    }

    // Add allowed cover weights
    coverWeights.forEach(function(weightInfo) {
        // Validate weightInfo structure and weight value
        // Note: typeof null returns 'object', so explicit null check is needed
        if (weightInfo && typeof weightInfo === 'object' && 
            typeof weightInfo.weight !== 'undefined' && weightInfo.weight !== null) {
            const weight = String(weightInfo.weight); // Convert to string for safety
            $coverSelect.append(
                $('<option></option>')
                    .val(weight)
                    .text(weight)
            );
        }
    });
}
```

#### 2. به‌روزرسانی رویداد تغییر نوع صحافی / Updated Binding Type Change Handler

هنگامی که کاربر نوع صحافی را تغییر می‌دهد، تابع `updateCoverWeights()` فراخوانی می‌شود:

```javascript
$('#aof-binding-type').on('change', function() {
    if (tabeshAdminOrderForm.v2Enabled) {
        updateCoverWeights();  // ← جدید
        updateExtrasAvailability();
    }
});
```

#### 3. ذخیره‌سازی داده‌های گرماژ جلد / Store Cover Weights Data

تابع `populateAllowedOptions()` به‌روزرسانی شد تا داده‌های `cover_weights` را در المان‌های option نوع صحافی ذخیره کند:

```javascript
data.allowed_bindings.forEach(function(binding) {
    const $option = $('<option></option>')
        .val(binding.type)
        .text(binding.type)
        .data('cover_weights', binding.cover_weights || []); // ← جدید
    $bindingTypeSelect.append($option);
});
```

#### 4. فعال‌سازی هنگام بازیابی انتخاب / Trigger on Restore Selection

هنگامی که انتخاب قبلی کاربر بازیابی می‌شود، فیلتر گرماژ جلد نیز فعال می‌شود:

```javascript
if (currentBindingType) {
    const isValid = data.allowed_bindings.some(function(b) { return b.type === currentBindingType; });
    if (isValid) {
        $bindingTypeSelect.val(currentBindingType);
        // Trigger cover weight update ← جدید
        if (tabeshAdminOrderForm.v2Enabled) {
            updateCoverWeights();
        }
    }
}
```

## نحوه کارکرد / How It Works

### جریان کار / Workflow

1. **انتخاب قطع کتاب / Book Size Selection**
   - کاربر قطع کتاب را انتخاب می‌کند
   - تابع `updateFormParametersForBookSize()` فراخوانی می‌شود
   - API `/get-allowed-options` برای دریافت پارامترهای مجاز فراخوانی می‌شود

2. **بارگذاری نوع‌های صحافی / Loading Binding Types**
   - نوع‌های صحافی مجاز برای قطع انتخاب شده دریافت می‌شوند
   - هر نوع صحافی با لیست گرماژ‌های جلد مجاز خود ذخیره می‌شود
   - داده‌ها در attribute `data-cover_weights` ذخیره می‌شوند

3. **انتخاب نوع صحافی / Binding Type Selection**
   - کاربر نوع صحافی را انتخاب می‌کند
   - رویداد `change` فعال می‌شود
   - تابع `updateCoverWeights()` فراخوانی می‌شود

4. **فیلتر کردن گرماژ جلد / Filtering Cover Weights**
   - گرماژ‌های جلد مجاز از option انتخاب شده خوانده می‌شوند
   - فیلد select گرماژ جلد پاک می‌شود
   - فقط گرماژ‌های مجاز به dropdown اضافه می‌شوند

### مثال / Example

فرض کنید:
- قطع: **A5**
- نوع صحافی انتخاب شده: **شومیز**
- گرماژ‌های مجاز برای شومیز: **250، 300، 350**

هنگامی که کاربر "شومیز" را انتخاب می‌کند، فقط گرماژ‌های 250، 300 و 350 در dropdown گرماژ جلد نمایش داده می‌شوند.

## سازگاری / Compatibility

### حالت V2 فعال / V2 Enabled
✅ **فعال** - فیلتر آبشاری کامل با استفاده از pricing matrices

### حالت V2 غیرفعال / V2 Disabled
✅ **بدون تاثیر** - تمام گرماژ‌ها از تنظیمات نمایش داده می‌شوند (رفتار قبلی)

## تست / Testing

### تست دستی / Manual Testing

1. **پیش‌نیازها / Prerequisites**
   - موتور قیمت‌گذاری V2 فعال باشد
   - حداقل یک قطع کتاب پیکربندی شده باشد
   - نوع‌های صحافی با گرماژ‌های جلد مختلف تعریف شده باشند

2. **مراحل تست / Test Steps**

   **مرحله 1: بررسی حالت اولیه**
   - به صفحه‌ای که شورتکد `[tabesh_admin_order_form]` دارد بروید
   - بررسی کنید که فیلد گرماژ جلد گزینه‌های اولیه دارد

   **مرحله 2: انتخاب قطع**
   - یک قطع کتاب انتخاب کنید (مثلاً A5)
   - بررسی کنید که نوع‌های صحافی مجاز بارگذاری شدند

   **مرحله 3: تست فیلتر گرماژ جلد**
   - یک نوع صحافی انتخاب کنید
   - باز کردن dropdown گرماژ جلد
   - **انتظار:** فقط گرماژ‌های مجاز برای آن نوع صحافی نمایش داده شوند

   **مرحله 4: تغییر نوع صحافی**
   - نوع صحافی دیگری انتخاب کنید
   - باز کردن dropdown گرماژ جلد
   - **انتظار:** گزینه‌های گرماژ جلد به‌روزرسانی شوند

3. **نکات تست / Test Notes**
   - با مرورگر Console (F12) پیام‌های خطا را بررسی کنید
   - Network tab را برای بررسی درخواست‌های API چک کنید
   - تست را با نوع‌های صحافی مختلف تکرار کنید

### بررسی Console / Console Verification

برای دیباگ، می‌توانید این کد را در Console مرورگر اجرا کنید:

```javascript
// بررسی داده‌های نوع صحافی انتخاب شده
const selectedBinding = $('#aof-binding-type option:selected');
console.log('Selected binding:', selectedBinding.val());
console.log('Cover weights:', selectedBinding.data('cover_weights'));

// بررسی گزینه‌های فعلی گرماژ جلد
const coverOptions = $('#aof-cover-paper-weight option').map(function() {
    return $(this).val();
}).get();
console.log('Current cover weight options:', coverOptions);
```

## مستندات مرتبط / Related Documentation

- `ADMIN_ORDER_FORM_V2_INTEGRATION.md` - اسناد یکپارچه‌سازی V2
- `DEPENDENCY_ENGINE_V2_GUIDE.md` - راهنمای موتور وابستگی
- `CASCADING_FILTERS_FIX.md` - اصلاحات فیلترهای آبشاری
- `assets/js/order-form-v2.js` - پیاده‌سازی مرجع در فرم V2

## یادداشت‌های توسعه / Developer Notes

### الگوی طراحی / Design Pattern

این پیاده‌سازی از همان الگوی استفاده شده در `order-form-v2.js` پیروی می‌کند:

1. **ذخیره‌سازی داده در DOM** - استفاده از jQuery `.data()` برای نگهداری متادیتا
2. **فیلترهای آبشاری** - هر انتخاب، گزینه‌های بعدی را فیلتر می‌کند
3. **بازیابی هوشمند** - انتخاب‌های قبلی در صورت امکان بازیابی می‌شوند

### نکات امنیتی / Security Considerations

✅ تمام ورودی‌ها از API دریافت می‌شوند (سرور قابل اعتماد)
✅ داده‌ها قبل از درج در DOM escape می‌شوند
✅ بدون اجرای کد JavaScript دلخواه

### عملکرد / Performance

- **حداقل درخواست API** - داده‌ها یک بار در انتخاب قطع دریافت می‌شوند
- **عملیات سریع DOM** - فقط یک select به‌روزرسانی می‌شود
- **بدون race condition** - عملیات همگام هستند

## نسخه / Version

- **افزوده شده در:** v1.0.4
- **تاریخ:** December 2024
- **توسط:** GitHub Copilot
- **مرتبط با PR:** #166

## سوالات متداول / FAQ

**Q: آیا این تغییر روی حالت غیر V2 تاثیر می‌گذارد؟**
A: خیر، فقط زمانی فعال می‌شود که `tabeshAdminOrderForm.v2Enabled` برابر `true` باشد.

**Q: اگر نوع صحافی گرماژ جلد نداشته باشد چه می‌شود؟**
A: dropdown خالی می‌ماند با یک گزینه پیش‌فرض "انتخاب کنید...".

**Q: آیا گرماژ جلد اجباری است؟**
A: خیر، در template فیلد required نیست، بنابراین اختیاری است.

**Q: چگونه می‌توانم ببینم چه گرماژ‌هایی برای یک نوع صحافی مجاز هستند؟**
A: در صفحه تنظیمات قیمت‌گذاری (Product Pricing)، در بخش "هزینه‌های صحافی"، می‌توانید گرماژ‌های مختلف را برای هر نوع صحافی تعریف کنید. همچنین می‌توانید گرماژ‌های ممنوع را در بخش "محدودیت‌ها" مشخص کنید.
