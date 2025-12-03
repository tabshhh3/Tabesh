# راهنمای رفع مشکل دانلود فایل توسط مدیر

## مقدمه

این راهنما به رفع مشکل دانلود فایل در پنل مدیریت (`[tabesh_admin_dashboard]`) می‌پردازد که در آن مدیر سایت نمی‌تواند فایل‌های سفارش را دانلود کند، در حالی که مشتریان (`[tabesh_upload_manager]`) بدون مشکل فایل‌ها را دانلود می‌کنند.

## علت مشکل

### قبل از رفع (نسخه‌های قبل از 1.0.2)

مدیران از روش `fetch()` با هدرهای CORS برای دانلود فایل استفاده می‌کردند:

```javascript
fetch(downloadUrl, {
    method: 'GET',
    credentials: 'same-origin',
    mode: 'cors'
})
```

این روش باعث ارسال **CORS Preflight Request** (یک درخواست OPTIONS قبل از درخواست اصلی) می‌شد که توسط CDN/Firewall بلاک می‌گردید و خطای `403 Forbidden` ایجاد می‌کرد.

### بعد از رفع (نسخه 1.0.2 و بالاتر)

مدیران اکنون از همان روش ساده مشتریان استفاده می‌کنند:

```javascript
window.open(response.download_url, '_blank');
```

این روش **بدون CORS Preflight** عمل می‌کند و فایل را مستقیماً باز می‌کند.

## تفاوت بین دانلود مدیر و مشتری

| ویژگی | مدیر (قبل از رفع) | مشتری | مدیر (بعد از رفع) |
|-------|-------------------|-------|-------------------|
| روش دانلود | `fetch()` + Blob | `window.open()` | `window.open()` |
| CORS Preflight | ✅ دارد | ❌ ندارد | ❌ ندارد |
| بلاک توسط CDN | ✅ بله | ❌ خیر | ❌ خیر |
| عملکرد | ❌ خطا 403 | ✅ موفق | ✅ موفق |

## چرا `fetch()` مشکل ایجاد می‌کرد؟

### CORS Preflight چیست؟

زمانی که از `fetch()` با `mode: 'cors'` استفاده می‌شود، مرورگر قبل از ارسال درخواست اصلی، یک درخواست OPTIONS ارسال می‌کند تا بررسی کند آیا سرور اجازه CORS را می‌دهد یا خیر.

**مثال:**
```http
OPTIONS /wp-content/uploads/tabesh-files/user-123/order-456/file.pdf HTTP/1.1
Host: example.com
Origin: https://example.com
Access-Control-Request-Method: GET
Access-Control-Request-Headers: x-wp-nonce
```

**مشکل:** بسیاری از CDN/Firewall ها (مانند Cloudflare، Sucuri، و...) این درخواست OPTIONS را به عنوان یک درخواست مشکوک می‌شناسند و آن را بلاک می‌کنند.

### چرا `window.open()` کار می‌کند؟

`window.open()` یک درخواست مرورگر ساده (Simple Request) ایجاد می‌کند که:
- نیازی به CORS Preflight ندارد
- مستقیماً فایل را از سرور دریافت می‌کند
- مانند کلیک کاربر روی یک لینک عمل می‌کند
- توسط CDN/Firewall بلاک نمی‌شود

## بررسی نسخه فعلی

برای اطمینان از اینکه نسخه رفع شده را دارید:

### 1. بررسی نسخه افزونه

```php
// در فایل tabesh.php
define('TABESH_VERSION', '1.0.2');
```

اگر نسخه شما `1.0.2` یا بالاتر است، رفع اعمال شده است.

### 2. بررسی کد JavaScript

باز کردن فایل `assets/js/admin-dashboard.js` و جستجوی متد `handleFileDownload`:

```javascript
// نسخه رفع شده (✅ صحیح)
handleFileDownload: function(e) {
    // ...
    success: (response) => {
        if (response.success && response.download_url) {
            // استفاده از window.open
            window.open(response.download_url, '_blank');
            this.showToast('دانلود شروع شد', 'success');
        }
    }
}

// نسخه قدیمی (❌ مشکل‌دار)
handleFileDownload: function(e) {
    // ...
    fetch(response.download_url, {
        method: 'GET',
        credentials: 'same-origin',
        mode: 'cors'
    })
}
```

## تست دانلود فایل

### 1. دانلود توسط مدیر

1. وارد پنل مدیریت شوید
2. به صفحه `[tabesh_admin_dashboard]` بروید
3. یک سفارش را انتخاب کنید
4. روی دکمه **دانلود فایل** کلیک کنید
5. فایل باید در تب جدید باز شود

### 2. بررسی Console مرورگر

اگر مشکل همچنان وجود دارد، Console مرورگر را باز کنید (F12) و به دنبال خطاهای زیر بگردید:

**خطای CORS (نسخه قدیمی):**
```
Access to fetch at '...' from origin '...' has been blocked by CORS policy
```

**خطای 403 (نسخه قدیمی):**
```
Failed to fetch
GET https://example.com/wp-content/uploads/... 403 (Forbidden)
```

**موفقیت (نسخه جدید):**
```
Tabesh: Download started successfully
```

## رفع مشکلات باقیمانده

اگر پس از بروزرسانی به نسخه 1.0.2 همچنان مشکل دارید:

### 1. پاک کردن Cache مرورگر

```
Ctrl + Shift + Delete (در اکثر مرورگرها)
یا
Ctrl + F5 (رفرش سخت)
```

### 2. پاک کردن Cache CDN

اگر از CDN استفاده می‌کنید (مانند Cloudflare):
1. وارد پنل CDN شوید
2. گزینه **Purge Cache** را انتخاب کنید
3. فایل‌های JavaScript را پاک کنید

### 3. غیرفعال کردن Minification

در CDN خود (مثلاً Cloudflare):
1. به **Speed** > **Optimization** بروید
2. **JavaScript Minification** را موقتاً غیرفعال کنید
3. دوباره تست کنید

### 4. بررسی دسترسی فایل‌ها

مطمئن شوید که فایل‌ها در مسیر صحیح هستند:

```bash
cd wp-content/uploads/tabesh-files/
ls -la
```

فایل `.htaccess` باید وجود داشته باشد و محتوای زیر را داشته باشد:

```apache
# Tabesh Files Protection
Order Deny,Allow
Deny from all
```

این تنظیم صحیح است و دسترسی مستقیم را بلاک می‌کند، اما دانلود از طریق توکن کار می‌کند.

### 5. بررسی توکن دانلود

توکن دانلود باید موقت باشد و پس از استفاده یا انقضا باطل شود:

```php
// در class-tabesh-file-security.php
public function generate_download_token($file_id, $user_id, $expiry_hours = 24)
```

مطمئن شوید که:
- توکن با موفقیت تولید می‌شود
- URL دانلود شامل پارامترهای `file_id` و `token` است
- توکن منقضی نشده است

### 6. لاگ‌های خطا

اگر `WP_DEBUG` فعال است، لاگ‌ها را بررسی کنید:

```bash
tail -f wp-content/debug.log
```

به دنبال خطاهای مرتبط با Tabesh بگردید:

```
Tabesh Security: [file_downloaded] User 1, File 123
Tabesh SMS: SMS disabled for status "pending"
```

## امنیت دانلود فایل

### چگونه امنیت حفظ می‌شود؟

حتی با استفاده از `window.open()`, امنیت دانلود فایل حفظ می‌شود:

1. **توکن موقت:** هر دانلود نیاز به توکن یکبار مصرف دارد
2. **محدودیت زمانی:** توکن بعد از 24 ساعت منقضی می‌شود
3. **بررسی مالکیت:** فقط صاحب فایل یا مدیر می‌تواند توکن بگیرد
4. **لاگ امنیتی:** تمام دانلودها در `wp_tabesh_security_logs` ثبت می‌شوند

### Flow امنیتی

```
1. کاربر روی دکمه دانلود کلیک می‌کند
2. درخواست AJAX به /files/generate-token ارسال می‌شود
3. سرور بررسی می‌کند:
   - آیا کاربر احراز هویت شده است?
   - آیا کاربر مجاز به دسترسی به این فایل است?
4. اگر مجاز بود، توکن تولید می‌شود و ذخیره می‌شود
5. URL دانلود با توکن به مرورگر برگردانده می‌شود
6. window.open() URL را باز می‌کند
7. سرور توکن را تایید و فایل را سرو می‌کند
8. توکن به عنوان استفاده شده علامت‌گذاری می‌شود
```

## خلاصه تغییرات نسخه 1.0.2

### فایل‌های تغییر یافته

1. **assets/js/admin-dashboard.js**
   - حذف روش `fetch()` + Blob
   - حذف fallback های iframe
   - اضافه کردن روش ساده `window.open()`

### مزایا

- ✅ رفع خطای 403 Forbidden
- ✅ عملکرد یکسان برای مدیر و مشتری
- ✅ سازگاری با CDN/Firewall
- ✅ کد ساده‌تر و قابل نگهداری‌تر

### عملکرد

- قبل: `fetch() → CORS Preflight → 403 Error`
- بعد: `window.open() → Direct Download → Success`

## پشتیبانی

اگر همچنان مشکل دارید:

1. نسخه افزونه را به 1.0.2 یا بالاتر بروزرسانی کنید
2. Cache مرورگر و CDN را پاک کنید
3. Console مرورگر را برای خطاها بررسی کنید
4. لاگ‌های سرور را بررسی کنید (`wp-content/debug.log`)
5. مستندات را مطالعه کنید

## نسخه و تاریخ

- **نسخه افزونه:** 1.0.2+
- **تاریخ رفع:** 1403/09/12
- **تاریخ آخرین بروزرسانی:** 1403/09/12
