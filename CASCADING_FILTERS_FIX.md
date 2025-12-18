# بازسازی فیلترهای آبشاری فرم V2 - مستندات

## خلاصه تغییرات

این اصلاح مشکل فیلترهای آبشاری (Cascading Filters) در فرم سفارش V2 را برطرف می‌کند.

## مسئله

فرم `[tabesh_order_form_v2]` گزینه‌های اولیه را نمایش می‌داد، اما هنگام تغییر هر فیلد، فیلدهای بعدی (مانند کاغذ یا صحافی) بر اساس محدودیت‌های مدیر بروزرسانی نمی‌شدند.

### علت ریشه‌ای
- Backend (Constraint_Manager) به درستی محدودیت‌ها را پردازش می‌کرد ✓
- REST API endpoint `/get-allowed-options` موجود بود و کار می‌کرد ✓
- اما JavaScript فقط یکبار پس از انتخاب `book_size` درخواست AJAX می‌فرستاد
- برای انتخاب‌های بعدی، از داده‌های کش شده (data attributes) استفاده می‌کرد
- این باعث می‌شد که محدودیت‌های متقابل (cross-field restrictions) اعمال نشوند

### مثال مشکل
```
1. کاربر قطع "A5" را انتخاب می‌کند
   → همه کاغذها: [تحریر، بالک، گلاسه]
   → همه صحافی‌ها: [شومیز، سیمی، گالینگور]

2. کاربر کاغذ "گلاسه" را انتخاب می‌کند
   ✗ صحافی "گالینگور" ممکن است برای این ترکیب ممنوع شود
   ✗ اما لیست صحافی‌ها بروزرسانی نمی‌شد!
   
3. کاربر می‌توانست "گالینگور" ممنوع را انتخاب کند
   → خطا در محاسبه قیمت یا ثبت سفارش
```

## راه‌حل

### 1. تابع جدید: `refreshAllowedOptionsFromSelection()`

این تابع پس از هر تغییر در انتخاب‌ها فراخوانی می‌شود و:
- تمام انتخاب‌های فعلی کاربر را جمع‌آوری می‌کند
- درخواست AJAX به `/get-allowed-options` می‌فرستد
- لیست bindings و print_types را بروزرسانی می‌کند

```javascript
function refreshAllowedOptionsFromSelection() {
    // Build current selection from form state
    const currentSelection = {};
    if (formState.paper_type) {
        currentSelection.paper_type = formState.paper_type;
    }
    if (formState.paper_weight) {
        currentSelection.paper_weight = formState.paper_weight;
    }
    // ... send to backend
}
```

### 2. Reset خودکار انتخاب‌های Downstream

هر event handler حالا انتخاب‌های بعدی را reset می‌کند:

```javascript
// Paper type selection
$('#paper_type_v2').on('change', function() {
    const paperType = $(this).val();
    
    formState.paper_type = paperType;
    // Reset downstream selections
    formState.paper_weight = '';
    formState.print_type = '';
    formState.binding_type = '';
    formState.cover_weight = '';
    formState.extras = [];
    
    // Refresh allowed options
    refreshAllowedOptionsFromSelection();
    loadPaperWeights(paperType);
});
```

### 3. حفظ انتخاب‌های معتبر

تابع `populateBindingTypes()` حالا:
- انتخاب فعلی را بررسی می‌کند
- اگر هنوز معتبر است → حفظ می‌شود
- اگر دیگر معتبر نیست → پاک می‌شود و فیلدهای بعدی مخفی می‌شوند

```javascript
function populateBindingTypes(bindings) {
    const currentValue = $select.val();
    // ... populate options
    
    // Restore selection if still valid
    if (currentValueStillValid) {
        $select.val(currentValue);
    } else if (currentValue) {
        console.log('Binding type no longer available, clearing');
        formState.binding_type = '';
        hideStepsAfter(7);
    }
}
```

## جریان کار بعد از اصلاح

```
1. کاربر قطع A5 را انتخاب می‌کند
   → AJAX: /get-allowed-options (book_size: A5)
   → دریافت: papers + bindings + print_types

2. کاربر کاغذ "گلاسه" را انتخاب می‌کند
   → AJAX: /get-allowed-options (book_size: A5, paper_type: گلاسه)
   → دریافت: bindings فیلتر شده (فقط مجاز برای گلاسه)
   → loadPaperWeights() → نمایش گرماژهای مجاز

3. کاربر گرماژ "70" را انتخاب می‌کند
   → AJAX: /get-allowed-options (book_size: A5, paper_type: گلاسه)
   → دریافت: print_types مجاز

4. کاربر نوع چاپ را انتخاب می‌کند
   → نمایش تعداد صفحات

5. کاربر تعداد صفحات و تیراژ را وارد می‌کند
   → نمایش لیست صحافی (که قبلاً فیلتر شده)

6. کاربر صحافی را انتخاب می‌کند
   → بارگذاری گرماژ جلد و خدمات اضافی

7. محاسبه قیمت و ثبت سفارش ✓
```

## تاثیر بر عملکرد

### Pros ✅
- ✅ فیلترهای آبشاری به درستی کار می‌کنند
- ✅ کاربر نمی‌تواند ترکیب‌های ممنوع را انتخاب کند
- ✅ خطاهای "ترکیب نامعتبر" در هنگام محاسبه قیمت کاهش می‌یابد
- ✅ تجربه کاربری بهتر (فقط گزینه‌های معتبر نمایش داده می‌شوند)

### Performance Considerations ⚠️
- ⚠️ تعداد درخواست‌های AJAX افزایش یافته (1-2 درخواست اضافی در هر فرم)
- ✅ Cache کردن در سمت سرور این تاثیر را کاهش می‌دهد
- ✅ Loading indicator نمایش داده می‌شود تا کاربر منتظر بماند

## تست

### سناریوهای تست

1. **تست فیلتر صحافی بر اساس کاغذ:**
   - قطع A5 را انتخاب کنید
   - کاغذ "گلاسه" را انتخاب کنید
   - بررسی کنید که فقط صحافی‌های مجاز نمایش داده شوند

2. **تست حفظ انتخاب معتبر:**
   - قطع A5، کاغذ "تحریر"، صحافی "شومیز" را انتخاب کنید
   - کاغذ را به "بالک" تغییر دهید
   - اگر "شومیز" برای "بالک" هم مجاز است → باید حفظ شود
   - اگر نه → باید پاک شود

3. **تست Reset خودکار:**
   - تمام فیلدها را پر کنید
   - قطع را تغییر دهید
   - بررسی کنید که همه انتخاب‌های بعدی پاک شوند

4. **تست محاسبه قیمت:**
   - یک ترکیب کامل انتخاب کنید
   - قیمت را محاسبه کنید
   - سفارش را ثبت کنید
   - بررسی کنید که بدون خطا ثبت شود

### Console Logs برای Debug

در حالت `WP_DEBUG` فعال:
```
Book size selected: A5
Refreshing allowed options with selection: {paper_type: "گلاسه"}
Refreshed options response: {success: true, data: {...}}
```

## فایل‌های تغییر یافته

- `assets/js/order-form-v2.js` - منطق فرم و AJAX

## Backward Compatibility

این تغییرات backward compatible هستند:
- ✅ API endpoint تغییری نکرده
- ✅ Backend logic تغییری نکرده
- ✅ فقط رفتار frontend بهبود یافته

## مستندات مرتبط

- [DEPENDENCY_ENGINE_V2_GUIDE.md](DEPENDENCY_ENGINE_V2_GUIDE.md) - راهنمای موتور منطق شرطی
- [V2_FORM_FIX_SUMMARY.md](V2_FORM_FIX_SUMMARY.md) - خلاصه اصلاحات قبلی
- [ORDER_FORM_V2_GUIDE.md](ORDER_FORM_V2_GUIDE.md) - راهنمای کامل فرم V2
