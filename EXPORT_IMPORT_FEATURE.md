# قابلیت برونبری و درونریزی (Export/Import Feature)

## خلاصه
این سند قابلیت جدید برونبری و درونریزی کامل برای افزونه تابش را توضیح می‌دهد که امکان پشتیبان‌گیری و بازیابی اطلاعات را فراهم می‌کند.

## ویژگی‌های پیاده‌سازی شده

### 1. کلاس مدیریت برونبری/درونریزی
**فایل:** `includes/handlers/class-tabesh-export-import.php`

کلاس `Tabesh_Export_Import` شامل تمام منطق مربوط به برونبری و درونریزی است:

#### متدهای عمومی:
- `export($sections)` - برونبری بخش‌های انتخاب شده
- `import($data, $sections, $mode)` - درونریزی با حالت merge یا replace
- `validate_import_data($data)` - اعتبارسنجی فایل ورودی
- `get_export_preview($sections)` - پیش‌نمایش قبل از برونبری
- `get_import_preview($data)` - پیش‌نمایش قبل از درونریزی
- `get_available_sections()` - لیست بخش‌های قابل برونبری/درونریزی

#### متدهای خصوصی:
برای هر بخش دو متد خصوصی وجود دارد:
- `export_*()` - برونبری دیتای آن بخش
- `import_*()` - درونریزی دیتای آن بخش

### 2. بخش‌های قابل برونبری/درونریزی

کلاس از برونبری/درونریزی 12 بخش اطلاعاتی پشتیبانی می‌کند:

1. **سفارشات** (`orders`) - جدول `wp_tabesh_orders`
2. **تنظیمات** (`settings`) - جدول `wp_tabesh_settings`
3. **مشتریان** (`customers`) - کاربران مرتبط با سفارشات از `wp_users`
4. **تاریخچه رویدادها** (`logs`) - جدول `wp_tabesh_logs`
5. **فایل‌ها** (`files`) - جدول `wp_tabesh_files`
6. **نسخه‌های فایل** (`file_versions`) - جدول `wp_tabesh_file_versions`
7. **وظایف آپلود** (`upload_tasks`) - جدول `wp_tabesh_upload_tasks`
8. **تنظیمات فرمت کتاب** (`book_format_settings`) - جدول `wp_tabesh_book_format_settings`
9. **نظرات فایل** (`file_comments`) - جدول `wp_tabesh_file_comments`
10. **متادیتای اسناد** (`document_metadata`) - جدول `wp_tabesh_document_metadata`
11. **توکن‌های دانلود** (`download_tokens`) - جدول `wp_tabesh_download_tokens`
12. **لاگ‌های امنیتی** (`security_logs`) - جدول `wp_tabesh_security_logs`

### 3. REST API Endpoints

چهار endpoint جدید به namespace `tabesh/v1` اضافه شده است:

#### `POST /tabesh/v1/export`
برونبری بخش‌های انتخاب شده.

**پارامترها:**
- `sections` (array, اختیاری) - آرایه‌ای از نام بخش‌ها. اگر خالی باشد، همه بخش‌ها برونبری می‌شوند.

**پاسخ:**
```json
{
  "success": true,
  "data": {
    "version": "1.0.3",
    "export_date": "2025-12-08 10:00:00",
    "site_url": "https://example.com",
    "sections": {
      "orders": [...],
      "settings": [...]
    }
  }
}
```

#### `POST /tabesh/v1/import`
درونریزی داده‌ها.

**پارامترها:**
- `data` (object, ضروری) - داده‌های برونبری شده
- `sections` (array, اختیاری) - بخش‌های مورد نظر برای درونریزی
- `mode` (string, اختیاری) - حالت درونریزی: `merge` (پیش‌فرض) یا `replace`

**پاسخ:**
```json
{
  "success": true,
  "message": "درونریزی با موفقیت انجام شد",
  "results": {
    "orders": {
      "success": true,
      "message": "10 سفارش وارد شد"
    }
  }
}
```

#### `POST /tabesh/v1/import/validate`
اعتبارسنجی فایل قبل از درونریزی.

**پارامترها:**
- `data` (object, ضروری) - داده‌های برونبری شده

**پاسخ:**
```json
{
  "valid": true,
  "version": "1.0.3",
  "export_date": "2025-12-08 10:00:00",
  "site_url": "https://example.com",
  "sections": {
    "orders": {
      "label": "سفارشات",
      "count": 10
    }
  }
}
```

#### `GET /tabesh/v1/export/preview`
پیش‌نمایش تعداد رکوردها قبل از برونبری.

**پارامترها:**
- `sections` (array, اختیاری) - بخش‌های مورد نظر

**پاسخ:**
```json
{
  "success": true,
  "preview": {
    "orders": {
      "label": "سفارشات",
      "count": 10
    }
  }
}
```

### 4. رابط کاربری

#### تب جدید در صفحه تنظیمات
یک تب جدید با عنوان "برونبری و درونریزی" به صفحه تنظیمات (`/wp-admin/admin.php?page=tabesh-settings`) اضافه شده است.

**بخش برونبری:**
- چکباکس "انتخاب همه" برای انتخاب سریع تمام بخش‌ها
- چکباکس جداگانه برای هر یک از 12 بخش
- دکمه "نمایش پیش‌نمایش" برای نمایش تعداد رکوردها
- دکمه "برونبری داده‌ها" برای دانلود فایل JSON
- نمایش وضعیت و پیام‌های موفقیت/خطا

**بخش درونریزی:**
- انتخاب فایل JSON از سیستم
- دکمه "بررسی فایل" برای اعتبارسنجی و نمایش پیش‌نمایش
- نمایش اطلاعات فایل (نسخه، تاریخ، سایت مبدا)
- چکباکس‌ها برای انتخاب بخش‌های مورد نظر برای درونریزی
- رادیو باتن‌ها برای انتخاب حالت (ادغام یا جایگزینی)
- دکمه "درونریزی داده‌ها" برای اعمال تغییرات
- نمایش وضعیت و پیام‌های موفقیت/خطا

### 5. JavaScript

**فایل:** `assets/js/admin.js`

تابع `initExportImport()` اضافه شده که شامل:

- مدیریت انتخاب چکباکس‌ها (تک‌به‌تک و "انتخاب همه")
- درخواست AJAX برای پیش‌نمایش برونبری
- برونبری و ایجاد فایل JSON قابل دانلود
- خواندن و اعتبارسنجی فایل انتخاب شده برای درونریزی
- نمایش پیش‌نمایش قبل از درونریزی
- اعمال درونریزی با حالت انتخاب شده
- نمایش پیام‌های موفقیت/خطا و progress indicator

## ویژگی‌های امنیتی

1. **بررسی دسترسی:** تمام endpoint‌ها نیاز به capability `manage_woocommerce` دارند
2. **Nonce Verification:** تمام درخواست‌های AJAX شامل nonce هستند
3. **Sanitization:** تمام ورودی‌ها sanitize می‌شوند
4. **Prepared Statements:** برای جلوگیری از SQL Injection استفاده شده است
5. **Transaction Support:** درونریزی در یک transaction انجام می‌شود که در صورت خطا rollback می‌شود
6. **Version Check:** فایل‌های با نسخه بالاتر از نسخه فعلی رد می‌شوند

## فرمت فایل

فایل‌های برونبری شده با فرمت JSON و پسوند `.json` ذخیره می‌شوند:

```json
{
  "version": "1.0.3",
  "export_date": "2025-12-08 10:00:00",
  "site_url": "https://example.com",
  "sections": {
    "orders": [...],
    "settings": [...],
    ...
  }
}
```

نام فایل: `tabesh-backup-YYYY-MM-DDTHH-mm-ss.json`

## حالت‌های درونریزی

### Merge (ادغام)
در این حالت:
- رکوردهای موجود حفظ می‌شوند
- رکوردهای جدید اضافه می‌شوند
- رکوردهای تکراری (بر اساس ID) بروزرسانی می‌شوند

### Replace (جایگزینی)
در این حالت:
- تمام رکوردهای موجود در بخش‌های انتخاب شده حذف می‌شوند
- رکوردهای جدید از فایل اضافه می‌شوند
- ⚠️ **هشدار:** این عمل غیرقابل بازگشت است

## نکات مهم

1. **پشتیبان‌گیری:** قبل از استفاده از حالت Replace حتماً پشتیبان تهیه کنید
2. **فایل‌های فیزیکی:** این ابزار فقط اطلاعات دیتابیس را پشتیبان می‌گیرد، نه فایل‌های فیزیکی آپلود شده
3. **کاربران:** برای امنیت، کاربران جدید از فایل برونبری ایجاد نمی‌شوند، فقط اطلاعات کاربران موجود بروزرسانی می‌شود
4. **حجم فایل:** برای سایت‌های بزرگ با داده زیاد، برونبری ممکن است زمان‌بر باشد
5. **سازگاری نسخه:** فایل‌های برونبری شده با نسخه‌های قدیمی‌تر سازگار هستند، اما نسخه‌های جدیدتر نیستند

## تست‌های انجام شده

✅ تست‌های واحد (Unit Tests):
- تست instantiation کلاس
- تست متد export
- تست متد import
- تست validation
- تست preview methods

✅ تست‌های یکپارچگی (Integration Tests):
- تست چرخه کامل export/import
- تست حالت merge
- تست حالت replace
- تست error handling
- تست preview functionality

✅ تست‌های کد:
- PHP Syntax: بدون خطا
- JavaScript Syntax: بدون خطا
- WordPress Coding Standards: مطابق استانداردها (با چند هشدار minor)

## استفاده

### از طریق رابط کاربری:

1. به صفحه **تابش > تنظیمات** بروید
2. روی تب **"برونبری و درونریزی"** کلیک کنید
3. برای برونبری:
   - بخش‌های مورد نظر را انتخاب کنید
   - روی "برونبری داده‌ها" کلیک کنید
   - فایل JSON را ذخیره کنید
4. برای درونریزی:
   - فایل JSON را انتخاب کنید
   - روی "بررسی فایل" کلیک کنید
   - بخش‌های مورد نظر و حالت را انتخاب کنید
   - روی "درونریزی داده‌ها" کلیک کنید

### از طریق کد PHP:

```php
$tabesh = Tabesh();

// Export
$export_data = $tabesh->export_import->export(array('orders', 'settings'));
$json = json_encode($export_data);

// Import
$data = json_decode($json, true);
$result = $tabesh->export_import->import($data, array('orders'), 'merge');

if ($result['success']) {
    // Success
} else {
    // Handle error
}
```

### از طریق REST API:

```javascript
// Export
fetch('/wp-json/tabesh/v1/export', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        sections: ['orders', 'settings']
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Download or save data.data
    }
});

// Import
fetch('/wp-json/tabesh/v1/import', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        data: importData,
        sections: ['orders'],
        mode: 'merge'
    })
})
.then(response => response.json())
.then(result => {
    if (result.success) {
        // Success
    }
});
```

## پشتیبانی

برای گزارش مشکلات یا پیشنهادات، لطفاً یک issue در مخزن GitHub ایجاد کنید.

## توسعه‌دهندگان

- تمام کدها مطابق WordPress Coding Standards نوشته شده‌اند
- از Autoloader موجود در پروژه استفاده می‌شود
- پشتیبانی کامل از RTL و زبان فارسی
- سازگار با PHP 8.2.2+ و WordPress 6.8+

## نسخه

این قابلیت در نسخه 1.0.3 افزونه تابش اضافه شده است.
