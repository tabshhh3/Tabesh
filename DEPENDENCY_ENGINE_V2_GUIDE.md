# راهنمای موتور منطق شرطی (Dependency Engine) V2

## مقدمه

موتور منطق شرطی V2 یک سیستم پیشرفته برای مدیریت محدودیت‌های بین‌فیلدی و ارائه گزینه‌های مجاز بر اساس انتخاب‌های فعلی کاربر است. این سیستم برای پیاده‌سازی فرم‌های مرحله‌به‌مرحله (Step-by-Step Forms) طراحی شده است.

## معماری

### کلاس‌های اصلی

1. **`Tabesh_Constraint_Manager`** - مدیریت محدودیت‌ها و قیود
2. **`Tabesh_Pricing_Engine`** - موتور قیمت‌گذاری (منبع داده)
3. **REST API Endpoints** - رابط‌های ارتباطی با فرانت‌اند

### جریان داده

```
User Selection → REST API → Constraint Manager → Pricing Matrix → Allowed Options → Response
```

## REST API Endpoints

### 1. دریافت گزینه‌های اولیه

**مسیر:** `GET /wp-json/tabesh/v1/available-options`

**پارامترها:**
- `book_size` (required): قطع کتاب (مثال: A5, رقعی، وزیری)

**مثال درخواست:**
```javascript
fetch('/wp-json/tabesh/v1/available-options?book_size=A5')
  .then(response => response.json())
  .then(data => {
    console.log('Available papers:', data.allowed_papers);
    console.log('Available bindings:', data.allowed_bindings);
  });
```

**پاسخ نمونه:**
```json
{
  "success": true,
  "book_size": "A5",
  "allowed_papers": [
    {
      "type": "تحریر",
      "slug": "tahrir",
      "weights": [
        {"weight": "60", "slug": "tahrir-60"},
        {"weight": "70", "slug": "tahrir-70"},
        {"weight": "80", "slug": "tahrir-80"}
      ]
    },
    {
      "type": "بالک",
      "slug": "bulk",
      "weights": [
        {"weight": "70", "slug": "bulk-70"},
        {"weight": "80", "slug": "bulk-80"}
      ]
    }
  ],
  "allowed_bindings": [
    {
      "type": "شومیز",
      "slug": "shomiz",
      "cover_weights": [
        {"weight": "200", "slug": "200"},
        {"weight": "250", "slug": "250"}
      ]
    }
  ],
  "allowed_print_types": [
    {"type": "bw", "slug": "bw", "label": "سیاه و سفید"},
    {"type": "color", "slug": "color", "label": "رنگی"}
  ],
  "allowed_cover_weights": [],
  "allowed_extras": []
}
```

### 2. دریافت گزینه‌های مجاز بر اساس انتخاب فعلی

**مسیر:** `POST /wp-json/tabesh/v1/get-allowed-options`

**مورد استفاده:** برای فرم‌های مرحله‌به‌مرحله که بعد از انتخاب هر فیلد، فیلد بعدی را محدود می‌کنند.

**ورودی:**
```json
{
  "book_size": "A5",
  "current_selection": {
    "paper_type": "تحریر",
    "paper_weight": "70",
    "binding_type": "شومیز"
  }
}
```

**مثال استفاده:**
```javascript
async function updateAllowedOptions(currentSelection) {
  const response = await fetch('/wp-json/tabesh/v1/get-allowed-options', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      book_size: 'A5',
      current_selection: currentSelection
    })
  });
  
  const result = await response.json();
  
  if (result.success) {
    // به‌روزرسانی فیلترهای بعدی بر اساس allowed options
    updatePrintTypeOptions(result.data.allowed_print_types);
    updateCoverWeightOptions(result.data.allowed_cover_weights);
    updateExtrasOptions(result.data.allowed_extras);
  }
}

// استفاده در event handler
document.getElementById('paper-type').addEventListener('change', (e) => {
  const currentSelection = {
    paper_type: e.target.value,
    paper_weight: document.getElementById('paper-weight').value,
    binding_type: document.getElementById('binding-type').value
  };
  
  updateAllowedOptions(currentSelection);
});
```

**پاسخ نمونه:**
```json
{
  "success": true,
  "data": {
    "book_size": "A5",
    "allowed_papers": [...],
    "allowed_bindings": [...],
    "allowed_print_types": [
      {"type": "bw", "slug": "bw", "label": "سیاه و سفید"},
      {"type": "color", "slug": "color", "label": "رنگی"}
    ],
    "allowed_cover_weights": [
      {"weight": "200", "slug": "200"},
      {"weight": "250", "slug": "250"}
    ],
    "allowed_extras": [
      {
        "name": "لب گرد",
        "slug": "rounded-corner",
        "price": 1000,
        "type": "per_unit"
      },
      {
        "name": "شیرینک",
        "slug": "shrink",
        "price": 1500,
        "type": "per_unit"
      }
    ]
  }
}
```

### 3. اعتبارسنجی ترکیب کامل

**مسیر:** `POST /wp-json/tabesh/v1/validate-combination`

**مورد استفاده:** قبل از محاسبه قیمت یا ثبت سفارش، برای اطمینان از معتبر بودن ترکیب.

**ورودی:**
```json
{
  "book_size": "A5",
  "paper_type": "گلاسه",
  "paper_weight": "100",
  "binding_type": "جلد سخت",
  "cover_weight": "350"
}
```

**مثال استفاده:**
```javascript
async function validateBeforeSubmit(formData) {
  const response = await fetch('/wp-json/tabesh/v1/validate-combination', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
  });
  
  const result = await response.json();
  
  if (!result.allowed) {
    // نمایش پیام خطا به کاربر
    showError(result.message);
    
    // نمایش پیشنهادات جایگزین
    if (result.suggestions.length > 0) {
      showSuggestions(result.suggestions);
    }
    
    return false;
  }
  
  return true;
}

// استفاده قبل از submit
document.getElementById('order-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = getFormData();
  const isValid = await validateBeforeSubmit(formData);
  
  if (isValid) {
    // ادامه فرآیند ثبت سفارش
    submitOrder(formData);
  }
});
```

**پاسخ در صورت معتبر بودن:**
```json
{
  "success": true,
  "allowed": true,
  "status": "valid",
  "message": "ترکیب معتبر است",
  "suggestions": []
}
```

**پاسخ در صورت نامعتبر بودن:**
```json
{
  "success": true,
  "allowed": false,
  "status": "forbidden_paper_type",
  "message": "کاغذ گلاسه برای قطع A5 مجاز نیست",
  "suggestions": ["تحریر", "بالک"]
}
```

### 4. محاسبه قیمت (بهبود یافته)

**مسیر:** `POST /wp-json/tabesh/v1/calculate-price`

**تغییرات:** اکنون علاوه بر قیمت، گزینه‌های مجاز بعدی را هم برمی‌گرداند.

**ورودی:**
```json
{
  "book_size": "A5",
  "paper_type": "تحریر",
  "paper_weight": "70",
  "print_type": "bw",
  "binding_type": "شومیز",
  "cover_weight": "250",
  "page_count_bw": 100,
  "page_count_color": 0,
  "quantity": 50,
  "extras": ["لب گرد", "شیرینک"]
}
```

**پاسخ جدید:**
```json
{
  "success": true,
  "status": "valid",
  "message": "محاسبه با موفقیت انجام شد",
  "data": {
    "total_price": 125000,
    "price_per_book": 2500,
    "quantity": 50,
    "subtotal": 125000,
    "discount_percent": 0,
    "discount_amount": 0,
    "total_after_discount": 125000,
    "profit_margin_percent": 0,
    "profit_amount": 0,
    "page_count_total": 100,
    "pricing_engine": "v2_matrix",
    "breakdown": {
      "book_size": "A5",
      "pages_cost_bw": 35000,
      "pages_cost_color": 0,
      "total_pages_cost": 35000,
      "cover_cost": 0,
      "binding_cost": 5500,
      "extras_cost": 2000,
      "per_page_cost_bw": 350,
      "per_page_cost_color": 0
    }
  },
  "allowed_next_options": {
    "book_size": "A5",
    "allowed_papers": [...],
    "allowed_bindings": [...],
    "allowed_print_types": [...],
    "allowed_cover_weights": [...],
    "allowed_extras": [...]
  }
}
```

**مثال استفاده:**
```javascript
async function calculateAndUpdate(formData) {
  const response = await fetch('/wp-json/tabesh/v1/calculate-price', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
  });
  
  const result = await response.json();
  
  if (result.success && result.status === 'valid') {
    // نمایش قیمت
    displayPrice(result.data.total_price);
    
    // نمایش جزئیات
    displayBreakdown(result.data.breakdown);
    
    // به‌روزرسانی فیلترهای بعدی (اختیاری)
    if (result.allowed_next_options) {
      updateFormOptions(result.allowed_next_options);
    }
  } else {
    // نمایش خطا
    showError(result.message);
  }
}
```

## سیستم Slug

### مفهوم

برای جلوگیری از مشکلات نیم‌فاصله، تایپو و ناسازگاری، تمام عناوین فارسی به Slug های استاندارد تبدیل می‌شوند.

### Mapping جدول

| نوع | عنوان فارسی | Slug |
|-----|------------|------|
| کاغذ | تحریر | tahrir |
| کاغذ | بالک | bulk |
| کاغذ | گلاسه | glossy |
| صحافی | شومیز | shomiz |
| صحافی | جلد سخت | hard-cover |
| صحافی | گالینگور | galingoor |
| صحافی | سیمی | simi |
| صحافی | منگنه | mangane |
| چاپ | سیاه و سفید | bw |
| چاپ | رنگی | color |
| خدمات | لب گرد | rounded-corner |
| خدمات | خط تا | creasing |
| خدمات | شیرینک | shrink |
| خدمات | سوراخ | hole-punch |
| خدمات | شماره گذاری | numbering |
| سلفون | سلفون براق | glossy-lamination |
| سلفون | سلفون مات | matte-lamination |

### استفاده در PHP

```php
// تبدیل عنوان فارسی به slug
$constraint_manager = new Tabesh_Constraint_Manager();
$slug = $constraint_manager->slugify('تحریر'); // Returns: "tahrir"

// تبدیل slug به عنوان فارسی
$label = $constraint_manager->unslugify('tahrir'); // Returns: "تحریر"
```

### استفاده در JavaScript

```javascript
// Map slugs در فرانت‌اند
const slugToLabel = {
  'tahrir': 'تحریر',
  'bulk': 'بالک',
  'glossy': 'گلاسه',
  'shomiz': 'شومیز',
  'hard-cover': 'جلد سخت',
  // ...
};

const labelToSlug = {
  'تحریر': 'tahrir',
  'بالک': 'bulk',
  'گلاسه': 'glossy',
  'شومیز': 'shomiz',
  'جلد سخت': 'hard-cover',
  // ...
};

// استفاده
const slug = labelToSlug['تحریر']; // "tahrir"
const label = slugToLabel['tahrir']; // "تحریر"
```

## بهینه‌سازی با Caching

### سمت سرور (PHP)

نتایج `get_allowed_options` به مدت 5 دقیقه در `wp_cache` ذخیره می‌شوند:

```php
// کلید cache بر اساس پارامترها
$cache_key = 'tabesh_allowed_options_' . md5(wp_json_encode($params));

// بررسی cache
$cached = wp_cache_get($cache_key, 'tabesh');
if ($cached !== false) {
    return $cached;
}

// محاسبه و ذخیره در cache
$result = $constraint_manager->get_allowed_options($current_selection, $book_size);
wp_cache_set($cache_key, $result, 'tabesh', 300); // 5 minutes
```

### سمت کلاینت (JavaScript)

می‌توانید از localStorage برای کش کردن نتایج استفاده کنید:

```javascript
class TabeshCache {
  constructor(ttl = 300000) { // 5 minutes default
    this.ttl = ttl;
  }
  
  set(key, value) {
    const item = {
      value: value,
      expiry: Date.now() + this.ttl
    };
    localStorage.setItem(key, JSON.stringify(item));
  }
  
  get(key) {
    const itemStr = localStorage.getItem(key);
    if (!itemStr) return null;
    
    const item = JSON.parse(itemStr);
    if (Date.now() > item.expiry) {
      localStorage.removeItem(key);
      return null;
    }
    
    return item.value;
  }
}

// استفاده
const cache = new TabeshCache();

async function getAvailableOptions(bookSize, currentSelection) {
  const cacheKey = `allowed_options_${bookSize}_${JSON.stringify(currentSelection)}`;
  
  // بررسی cache
  const cached = cache.get(cacheKey);
  if (cached) {
    return cached;
  }
  
  // درخواست از API
  const response = await fetch('/wp-json/tabesh/v1/get-allowed-options', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({book_size: bookSize, current_selection: currentSelection})
  });
  
  const result = await response.json();
  
  // ذخیره در cache
  cache.set(cacheKey, result);
  
  return result;
}
```

## پیاده‌سازی فرم Step-by-Step

### مثال کامل

```javascript
class StepByStepForm {
  constructor(formElement) {
    this.form = formElement;
    this.constraintManager = new TabeshConstraintManager();
    this.currentStep = 1;
    this.formData = {};
    
    this.init();
  }
  
  init() {
    // مقداردهی اولیه فرم
    this.loadBookSizes();
    
    // رویدادها
    this.attachEventListeners();
  }
  
  async loadBookSizes() {
    // بارگذاری لیست قطع‌های کتاب
    const response = await fetch('/wp-json/tabesh/v1/available-options?book_size=A5');
    const result = await response.json();
    
    // نمایش انتخاب‌ها
    this.renderBookSizeOptions(result);
  }
  
  attachEventListeners() {
    // انتخاب قطع کتاب
    this.form.querySelector('#book-size').addEventListener('change', async (e) => {
      this.formData.book_size = e.target.value;
      await this.updateStep1Options();
      this.showStep(2);
    });
    
    // انتخاب نوع کاغذ
    this.form.querySelector('#paper-type').addEventListener('change', async (e) => {
      this.formData.paper_type = e.target.value;
      await this.updateStep2Options();
      this.showStep(3);
    });
    
    // انتخاب گرماژ کاغذ
    this.form.querySelector('#paper-weight').addEventListener('change', async (e) => {
      this.formData.paper_weight = e.target.value;
      await this.updateStep3Options();
      this.showStep(4);
    });
    
    // و غیره...
  }
  
  async updateStep1Options() {
    // بعد از انتخاب قطع، دریافت نوع کاغذهای مجاز
    const options = await this.constraintManager.getAllowedOptions(
      this.formData.book_size,
      {}
    );
    
    this.renderPaperTypeOptions(options.allowed_papers);
  }
  
  async updateStep2Options() {
    // بعد از انتخاب نوع کاغذ، دریافت گرماژهای مجاز
    const options = await this.constraintManager.getAllowedOptions(
      this.formData.book_size,
      { paper_type: this.formData.paper_type }
    );
    
    const selectedPaper = options.allowed_papers.find(
      p => p.type === this.formData.paper_type
    );
    
    this.renderPaperWeightOptions(selectedPaper.weights);
  }
  
  async updateStep3Options() {
    // بعد از انتخاب گرماژ، دریافت نوع صحافی‌های مجاز
    const options = await this.constraintManager.getAllowedOptions(
      this.formData.book_size,
      {
        paper_type: this.formData.paper_type,
        paper_weight: this.formData.paper_weight
      }
    );
    
    this.renderBindingOptions(options.allowed_bindings);
  }
  
  showStep(stepNumber) {
    // مخفی کردن همه مراحل
    this.form.querySelectorAll('.step').forEach(step => {
      step.classList.remove('active');
    });
    
    // نمایش مرحله فعلی
    this.form.querySelector(`#step-${stepNumber}`).classList.add('active');
    this.currentStep = stepNumber;
  }
  
  renderPaperTypeOptions(papers) {
    const select = this.form.querySelector('#paper-type');
    select.innerHTML = '<option value="">انتخاب کنید...</option>';
    
    papers.forEach(paper => {
      const option = document.createElement('option');
      option.value = paper.type;
      option.textContent = paper.type;
      option.dataset.slug = paper.slug;
      select.appendChild(option);
    });
  }
  
  renderPaperWeightOptions(weights) {
    const select = this.form.querySelector('#paper-weight');
    select.innerHTML = '<option value="">انتخاب کنید...</option>';
    
    weights.forEach(weight => {
      const option = document.createElement('option');
      option.value = weight.weight;
      option.textContent = `${weight.weight} گرم`;
      option.dataset.slug = weight.slug;
      select.appendChild(option);
    });
  }
  
  renderBindingOptions(bindings) {
    const container = this.form.querySelector('#binding-options');
    container.innerHTML = '';
    
    bindings.forEach(binding => {
      const label = document.createElement('label');
      label.className = 'binding-option';
      label.innerHTML = `
        <input type="radio" name="binding_type" value="${binding.type}" data-slug="${binding.slug}">
        <span>${binding.type}</span>
      `;
      container.appendChild(label);
    });
  }
}

// Helper class برای تعامل با API
class TabeshConstraintManager {
  constructor() {
    this.cache = new TabeshCache();
  }
  
  async getAllowedOptions(bookSize, currentSelection = {}) {
    const cacheKey = `allowed_${bookSize}_${JSON.stringify(currentSelection)}`;
    
    // بررسی cache
    const cached = this.cache.get(cacheKey);
    if (cached) return cached;
    
    // درخواست از API
    const response = await fetch('/wp-json/tabesh/v1/get-allowed-options', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        book_size: bookSize,
        current_selection: currentSelection
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      this.cache.set(cacheKey, result.data);
      return result.data;
    }
    
    throw new Error(result.message);
  }
  
  async validateCombination(formData) {
    const response = await fetch('/wp-json/tabesh/v1/validate-combination', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    return result;
  }
  
  async calculatePrice(formData) {
    const response = await fetch('/wp-json/tabesh/v1/calculate-price', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    return result;
  }
}

// استفاده
document.addEventListener('DOMContentLoaded', () => {
  const formElement = document.querySelector('#tabesh-order-form');
  if (formElement) {
    new StepByStepForm(formElement);
  }
});
```

## نکات امنیتی

### Sanitization

تمام ورودی‌ها در سمت سرور Sanitize می‌شوند:

```php
$book_size = sanitize_text_field($params['book_size'] ?? '');
$paper_type = sanitize_text_field($params['paper_type'] ?? '');
```

### Nonce Verification

برای درخواست‌های حساس از nonce استفاده کنید:

```javascript
fetch('/wp-json/tabesh/v1/submit-order', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': tabeshData.nonce  // از wp_localize_script
  },
  body: JSON.stringify(orderData)
});
```

### Rate Limiting

برای جلوگیری از سوءاستفاده، محدودیت تعداد درخواست اعمال کنید:

```javascript
class RateLimiter {
  constructor(maxRequests = 10, timeWindow = 60000) {
    this.maxRequests = maxRequests;
    this.timeWindow = timeWindow;
    this.requests = [];
  }
  
  canMakeRequest() {
    const now = Date.now();
    this.requests = this.requests.filter(time => now - time < this.timeWindow);
    
    if (this.requests.length >= this.maxRequests) {
      return false;
    }
    
    this.requests.push(now);
    return true;
  }
}

const rateLimiter = new RateLimiter(10, 60000); // 10 requests per minute

async function safeApiCall(url, options) {
  if (!rateLimiter.canMakeRequest()) {
    throw new Error('تعداد درخواست‌های شما از حد مجاز گذشته است. لطفا کمی صبر کنید.');
  }
  
  return fetch(url, options);
}
```

## خطایابی و Logging

### سمت سرور

برای فعال کردن debug mode در `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Log ها در `wp-content/debug.log` ذخیره می‌شوند.

### سمت کلاینت

```javascript
class DebugLogger {
  constructor(enabled = false) {
    this.enabled = enabled;
  }
  
  log(message, data = null) {
    if (!this.enabled) return;
    
    console.log(`[Tabesh Debug] ${message}`, data);
  }
  
  error(message, error = null) {
    if (!this.enabled) return;
    
    console.error(`[Tabesh Error] ${message}`, error);
  }
}

const logger = new DebugLogger(true); // Enable in development

// استفاده
logger.log('Fetching allowed options', {book_size: 'A5'});
logger.error('API call failed', error);
```

## پشتیبانی و مستندات بیشتر

- **GitHub Repository:** https://github.com/tabshhh3/Tabesh
- **Documentation:** README.md
- **API Reference:** API.md
- **Issues:** https://github.com/tabshhh3/Tabesh/issues

## تغییرات آتی (Roadmap)

- [ ] پشتیبانی از GraphQL
- [ ] WebSocket برای به‌روزرسانی real-time
- [ ] پیش‌نمایش محدودیت‌ها در پنل ادمین
- [ ] Import/Export تنظیمات محدودیت‌ها
- [ ] A/B Testing برای قوانین مختلف
