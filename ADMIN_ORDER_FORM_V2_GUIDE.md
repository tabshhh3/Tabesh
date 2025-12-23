# Admin Order Form V2 - Complete Guide
# راهنمای کامل فرم سفارش ادمین نسخه ۲

## مقدمه / Introduction

Admin Order Form V2 is a modern, matrix-based pricing form designed specifically for administrators to create orders on behalf of customers. It integrates seamlessly with the V2 pricing engine and provides a streamlined workflow for order creation.

فرم سفارش ادمین نسخه ۲ یک فرم پیشرفته مبتنی بر ماتریس قیمت‌گذاری است که به‌طور خاص برای مدیران طراحی شده تا بتوانند به نام مشتریان سفارش ثبت کنند. این فرم به‌طور یکپارچه با موتور قیمت‌گذاری V2 ادغام شده و یک گردش کار ساده برای ایجاد سفارش فراهم می‌کند.

---

## پیش‌نیازها / Prerequisites

### ۱. فعالسازی موتور قیمت‌گذاری V2

Before using this form, you **must** enable the V2 pricing engine:

1. Go to WordPress Admin → **Tabesh** → **Product Pricing**
2. Enable **"Activate Pricing Engine V2"** checkbox
3. Save settings
4. Configure pricing matrices for at least one book size

قبل از استفاده از این فرم، **باید** موتور قیمت‌گذاری V2 را فعال کنید:

1. به پنل مدیریت وردپرس → **تابش** → **قیمت‌گذاری محصول** بروید
2. گزینه **"فعالسازی موتور قیمت‌گذاری نسخه ۲"** را علامت بزنید
3. تنظیمات را ذخیره کنید
4. ماتریس قیمت را برای حداقل یک قطع کتاب پیکربندی کنید

### ۲. پیکربندی ماتریس قیمت

At least one book size must be configured in the pricing matrix:

1. Go to **Product Pricing** page
2. Select a book size (e.g., A5, رقعی, وزیری)
3. Configure:
   - Paper types and weights
   - Binding types
   - Cover weights
   - Extra services
   - Cross-restrictions (forbidden combinations)
4. Save the matrix

حداقل یک قطع کتاب باید در ماتریس قیمت پیکربندی شده باشد:

1. به صفحه **قیمت‌گذاری محصول** بروید
2. یک قطع کتاب را انتخاب کنید (مثلاً A5، رقعی، وزیری)
3. پیکربندی کنید:
   - نوع کاغذها و گرماژها
   - نوع صحافی‌ها
   - گرماژ جلدها
   - خدمات اضافی
   - محدودیت‌های متقاطع (ترکیبات ممنوع)
4. ماتریس را ذخیره کنید

### ۳. دسترسی مدیریتی

The form requires administrator-level access:

- Users with **manage_woocommerce** capability (administrators)
- Users in allowed roles (configurable in settings)
- Specific users in allowed users list (configurable in settings)

این فرم نیاز به دسترسی سطح مدیریتی دارد:

- کاربران با قابلیت **manage_woocommerce** (مدیران)
- کاربران در نقش‌های مجاز (قابل تنظیم در تنظیمات)
- کاربران خاص در لیست کاربران مجاز (قابل تنظیم در تنظیمات)

---

## استفاده از Shortcode / Using the Shortcode

### روش ساده / Simple Method

Add this shortcode to any page or post:

```
[tabesh_admin_order_form_v2]
```

این شورتکد را به هر صفحه یا پستی اضافه کنید:

```
[tabesh_admin_order_form_v2]
```

### با عنوان سفارشی / With Custom Title

```
[tabesh_admin_order_form_v2 title="ثبت سفارش جدید"]
```

### در قالب / In Theme Template

```php
<?php echo do_shortcode('[tabesh_admin_order_form_v2]'); ?>
```

---

## مراحل فرم / Form Steps

### مرحله ۱: انتخاب مشتری / Step 1: Customer Selection

The first step allows you to either search for an existing customer or create a new one.

اولین مرحله به شما اجازه می‌دهد یک مشتری موجود را جستجو کنید یا یک مشتری جدید ایجاد کنید.

#### جستجوی مشتری موجود / Search Existing Customer

1. Type customer name, mobile, or email in the search box
2. Wait for live search results (minimum 2 characters)
3. Click on a customer to select
4. Selected customer will be displayed with their information

**Features:**
- Live search with AJAX
- Search by name, mobile, or email
- Shows customer details instantly

#### ایجاد مشتری جدید / Create New Customer

1. Switch to "New Customer" tab
2. Fill in:
   - Full name (required)
   - Mobile number (required, format: 09123456789)
   - Email (optional)
3. Click "Create Customer" button
4. New customer will be created and automatically selected

**Features:**
- Real-time mobile validation
- Automatically creates WordPress user
- Seamless transition to order form

### مرحله ۲: اطلاعات اولیه کتاب / Step 2: Book Information

Enter book title and select book size.

عنوان کتاب را وارد کنید و قطع کتاب را انتخاب کنید.

**Fields:**
- **Book Title**: The name that will be printed on the book
- **Book Size**: Select from configured sizes (A5, رقعی, وزیری, etc.)

**ویژگی‌ها:**
- عنوان کتاب: نامی که روی جلد چاپ می‌شود
- قطع کتاب: از قطع‌های پیکربندی شده انتخاب کنید

### مرحله ۳: مشخصات کاغذ و چاپ / Step 3: Paper & Print Specifications

Configure paper type, weight, print type, page count, and quantity.

نوع کاغذ، گرماژ، نوع چاپ، تعداد صفحات و تیراژ را تنظیم کنید.

**Cascading Fields (فیلدهای آبشاری):**

1. **Paper Type**: Select from allowed paper types for chosen book size
2. **Paper Weight**: Automatically filtered based on selected paper type
3. **Print Type**: Dynamically shown based on paper type and weight restrictions
4. **Page Count**: Number of pages (must be multiple of 4)
5. **Quantity**: Print run quantity (respects min/max constraints)

**Smart Filtering:**
- Only allowed combinations are shown
- Forbidden options are automatically hidden
- Real-time constraint validation

### مرحله ۴: صحافی و جلد / Step 4: Binding & Cover

Select binding type, cover weight, and optional extra services.

نوع صحافی، گرماژ جلد و خدمات اضافی اختیاری را انتخاب کنید.

**Fields:**
- **Binding Type**: Choose from configured binding methods
- **Cover Weight**: Filtered based on binding type
- **Extra Services**: Check optional services (lamination, embossing, etc.)

**ویژگی‌ها:**
- گرماژ جلد بر اساس نوع صحافی فیلتر می‌شود
- خدمات اضافی به‌صورت چک‌باکس
- همه گزینه‌ها از ماتریس قیمت می‌آیند

### مرحله ۵: بررسی و تکمیل / Step 5: Review & Submit

Review order details, calculate price, add notes, and submit order.

جزئیات سفارش را بررسی کنید، قیمت را محاسبه کنید، توضیحات اضافه کنید و سفارش را ثبت کنید.

**Review Section:**
- Customer information
- Book specifications
- All selected options
- Complete order summary

**Price Calculation:**
1. Click "Calculate Price" button
2. V2 pricing engine calculates:
   - Price per unit
   - Total price
3. Prices displayed in Tomans

**Additional Options:**
- **Notes**: Add order-specific notes or instructions
- **Send SMS**: Check to send confirmation SMS to customer
  - Only sends if checkbox is checked
  - Uses configured SMS provider
  - Sends after successful order creation

**Submit:**
- Click "Submit Order" to finalize
- Order is created in WooCommerce
- Customer receives order confirmation
- Admin is redirected to order details

---

## ویژگی‌های پیشرفته / Advanced Features

### 1. محاسبه قیمت لحظه‌ای / Real-time Price Calculation

- Uses V2 pricing engine API
- Calculates based on current pricing matrix
- Shows per-unit and total price
- Instant calculation with AJAX

### 2. مدیریت محدودیت‌ها / Constraint Management

- Forbidden combinations are automatically hidden
- Cascading filters ensure only valid options are shown
- Respects all pricing matrix restrictions
- Prevents invalid parameter combinations

### 3. مدیریت کاربران / User Management

- Search existing WordPress users
- Create new users on-the-fly
- Validate mobile format (Iranian format)
- Seamless user selection

### 4. ارسال پیامک اختیاری / Optional SMS Sending

- Checkbox to enable/disable SMS
- Sends only if explicitly checked
- Uses configured SMS provider
- Respects SMS settings and balance

### 5. رابط کاربری مدرن / Modern UI

- 5-step wizard interface
- Progress bar showing current step
- Visual step indicators
- Smooth transitions
- Responsive design
- RTL support for Persian
- Toast notifications for feedback
- Loading states for async operations

---

## API Integration

The form integrates with the following REST API endpoints:

### 1. Customer Management
- **POST** `/tabesh/v1/admin/search-users-live` - Live search customers
- **POST** `/tabesh/v1/admin/create-user` - Create new customer

### 2. Price Calculation
- **POST** `/tabesh/v1/calculate-price` - Calculate order price
  ```json
  {
    "book_size": "A5",
    "paper_type": "تحریر",
    "paper_weight": "80",
    "print_type": "bw",
    "page_count": 100,
    "quantity": 50,
    "binding_type": "شومیز",
    "cover_weight": "300",
    "extras": ["سلفون"]
  }
  ```

### 3. Constraint Management
- **POST** `/tabesh/v1/get-allowed-options` - Get filtered options
  ```json
  {
    "book_size": "A5",
    "current_selection": {
      "paper_type": "تحریر",
      "paper_weight": "80"
    }
  }
  ```

### 4. Order Submission
- **POST** `/tabesh/v1/submit-order` - Submit order
  ```json
  {
    "customer_id": 123,
    "book_title": "کتاب نمونه",
    "book_size": "A5",
    "paper_type": "تحریر",
    "paper_weight": "80",
    "print_type": "bw",
    "page_count": 100,
    "quantity": 50,
    "binding_type": "شومیز",
    "cover_weight": "300",
    "extras": ["سلفون"],
    "notes": "توضیحات اضافی",
    "send_sms": true,
    "calculated_price": { ... }
  }
  ```

---

## تفاوت با نسخه‌های قبلی / Differences from Previous Versions

| Feature | Admin Order Form (Legacy) | Admin Order Form V2 |
|---------|---------------------------|---------------------|
| **Pricing Engine** | Legacy coefficients | V2 matrix-based |
| **Option Filtering** | Static - shows all options | Dynamic - shows only allowed |
| **Constraint Validation** | At submission | Real-time cascading |
| **UI/UX** | Compact horizontal layout | Modern 5-step wizard |
| **Customer Management** | Search only | Search + create |
| **Price Calculation** | Legacy algorithm | V2 API-based |
| **Mobile Format** | Basic validation | Iranian format (09xxxxxxxxx) |
| **SMS Control** | Always sent if enabled | Optional per-order |

---

## عیب‌یابی / Troubleshooting

### فرم نمایش داده نمی‌شود
**Form doesn't display**

**Problem:** "No book sizes configured" error
**Solution:**
1. Enable V2 pricing engine in Product Pricing page
2. Configure at least one book size pricing matrix
3. Ensure product parameters (book sizes) are defined in settings

### خطا در جستجوی مشتری
**Customer search error**

**Problem:** Search returns no results or error
**Solution:**
1. Check WordPress user roles and permissions
2. Verify REST API is accessible
3. Check browser console for AJAX errors
4. Ensure nonce is valid

### قیمت محاسبه نمی‌شود
**Price not calculating**

**Problem:** "Error calculating price" message
**Solution:**
1. Verify all required fields are filled
2. Check pricing matrix is configured for selected book size
3. Ensure all parameters have valid pricing entries
4. Check browser console for API errors

### سفارش ثبت نمی‌شود
**Order not submitting**

**Problem:** "Error submitting order" message
**Solution:**
1. Verify WooCommerce is active
2. Check user has sufficient permissions
3. Ensure all required fields are filled
4. Verify pricing was calculated before submission
5. Check WordPress debug log for errors

---

## دسترسی و امنیت / Access & Security

### Access Control

The form is protected by multiple access layers:

1. **WordPress Login**: Must be logged in
2. **Role-Based Access**:
   - Administrators (manage_woocommerce capability)
   - Custom allowed roles (configured in settings)
   - Specific allowed users (configured in settings)

### Security Features

- ✅ Nonce verification for all AJAX requests
- ✅ Input sanitization and validation
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ Role-based access control
- ✅ REST API authentication

### Configuring Access

To allow non-administrator users:

1. Go to **Tabesh** → **Settings**
2. Find **Admin Order Form Access** section
3. Configure:
   - **Allowed Roles**: Select WordPress roles
   - **Allowed Users**: Add specific user IDs
4. Save settings

---

## بهترین شیوه‌ها / Best Practices

### 1. قبل از استفاده
- Always test in a staging environment first
- Configure pricing matrices thoroughly
- Test all book size configurations
- Verify SMS provider settings if using SMS

### 2. هنگام استفاده
- Always calculate price before submission
- Verify customer information is correct
- Add notes for special instructions
- Use SMS option selectively to manage costs

### 3. بعد از استفاده
- Monitor order creation in WooCommerce
- Check SMS delivery status
- Review order details for accuracy
- Follow up with customers as needed

---

## سوالات متداول / FAQ

### Q: Can I use this form without V2 pricing engine?
**A:** No, this form requires V2 pricing engine to be enabled. Use `[tabesh_admin_order_form]` for legacy pricing.

### Q: How do I add more book sizes?
**A:** Go to Tabesh → Settings → Product Parameters, add book sizes, then configure pricing matrices for each.

### Q: Can customers use this form?
**A:** No, this form is admin-only. Customers should use `[tabesh_order_form_v2]` shortcode.

### Q: Does SMS always send?
**A:** No, SMS only sends if the checkbox is checked on step 5. This gives you per-order control.

### Q: How is price calculated?
**A:** Price is calculated using V2 pricing engine API, which uses the configured pricing matrix for the selected book size.

### Q: Can I create multiple orders simultaneously?
**A:** Yes, after submitting an order, you can click "Create Another Order" to start fresh.

---

## پشتیبانی / Support

For questions or issues:

- 📖 **Documentation**: Check this guide and related docs
- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/tabshhh4-sketch/Tabesh/issues)
- 💬 **Community**: [GitHub Discussions](https://github.com/tabshhh4-sketch/Tabesh/discussions)
- 📧 **Email**: support@chapco.ir

---

## لینک‌های مرتبط / Related Links

- [PRICING_ENGINE_V2.md](PRICING_ENGINE_V2.md) - V2 Pricing Engine Documentation
- [ORDER_FORM_V2_GUIDE.md](ORDER_FORM_V2_GUIDE.md) - Customer Order Form V2 Guide
- [DEPENDENCY_ENGINE_V2_GUIDE.md](DEPENDENCY_ENGINE_V2_GUIDE.md) - Constraint Manager Guide
- [README.md](README.md) - Main Plugin Documentation

---

Made with ❤️ for the Persian printing industry
