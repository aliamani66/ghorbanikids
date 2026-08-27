# راهنمای جامع معماری ماژول بازی‌ها و تسویه‌حساب (GhorbaniKids Games Manager v2)

این ماژول جایگزین استاندارد و ماژولار فایل تک‌فایلی قدیمی `ghorbanikids-games.php` است و بر اساس استانداردهای تفکیک وظایف و معماری تمیز (Clean Architecture) طراحی شده است.

---

## ۱. ساختار پوشه‌ها و فایل‌ها

```
ghorbanikids-games-manager/
├── ghorbanikids-games-manager.php       # لودر اصلی و تعریف ثوابت ماژول
├── GAMES_SYSTEM_GUIDE.md                # راهنمای سیستم و مستندات
├── includes/
│   ├── class-gk-utils.php               # توابع پایداری، رفع باگ Select2، کرون و ایمیل
│   ├── class-gk-cpt.php                 # ثبت CPT بازی‌ها (gk_game)، دسته‌ها و متاباکس‌های مدیریت
│   ├── class-gk-subscriptions.php       # مدیریت اشتراک VIP، متای کاربران و داشبورد حساب
│   ├── class-gk-checkout.php            # ساده‌سازی فیلدهای تسویه‌حساب ووکامرس و هوک سفارشات
│   ├── class-gk-pricing.php             # جدول تعرفه‌ها و شورت‌کد [ghorbanikids_pricing]
│   ├── class-gk-player.php              # رندر آی‌فریم پلیر بازی، بررسی قفل VIP و هدرهای No-Cache
│   ├── class-gk-catalog.php             # صفحه آرشیو بازی‌ها، دسته‌بندی و شورت‌کد [ghorbanikids_games]
│   └── class-gk-theme-bridge.php        # هدر شیشه‌ای اختصاصی، فوتر، منوی ناوبری و هماهنگی با قالب
└── assets/
    ├── css/
    │   └── gk-games-main.css            # استایل‌های تمیز و یکپارچه فرانت‌اند
    └── js/
        ├── gk-catalog-filter.js         # اسکریپت فیلتر و جستجوی فوق سریع بازی‌ها
        ├── gk-player.js                 # مدیریت تمام‌صفحه و فول‌اسکرین پلیر
        ├── gk-auth-tabs.js              # سوییچر تب‌های لاگین و ثبت‌نام
        └── gk-social-icons.js           # مدیریت آیکون‌های شبکه‌های اجتماعی
```

---

## ۲. نحوه فعال‌سازی و سوئیچ امن (Safe Switching)

- تمامی کدهای نسخه قدیمی بدون دستکاری در فایل `ghorbanikids-games.php` موجود هستند.
- جهت فعال‌سازی ماژول جدید:
  1. یک لودر `ghorbanikids-games-loader.php` در ریشه `wp-content/mu-plugins/` ایجاد می‌شود.
  2. نام فایل قدیمی به `ghorbanikids-games.php.legacy` تغییر می‌یابد تا نسخه جدید جایگزین گردد.
- جهت بازگشت آنی (Rollback):
  - بازگردانی نام فایل قدیمی به `ghorbanikids-games.php` در کمتر از ۵ ثانیه سیستم را به حالت قبل برمی‌گرداند.