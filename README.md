# 📚 Librax - Library Management System

نظام إدارة مكتبات متكامل يربط أصحاب المكتبات بالزبائن عبر تطبيق جوال وويب.

## 🎯 نظرة عامة

**Librax** هو نظام لإدارة المكتبات يتيح:
- لأصحاب المكتبات عرض وإدارة كتبهم
- للزبائن تصفح وشراء الكتب من مكتبات مختلفة
- للأدمن الإشراف على النظام بالكامل

## 👥 أنواع المستخدمين

### 1. الزبون (Customer)
- تصفح الكتب حسب الأصناف
- البحث والفلترة
- إضافة للمفضلة
- الشراء عبر المحفظة أو نقداً
- تتبع الطلبات
- تقييم الكتب

### 2. صاحب المكتبة (Library Owner)
- إدارة الكتب (إضافة، تعديل، حذف)
- إدارة الطلبات (قبول/رفض)
- عرض الإحصائيات
- إدارة المحفظة

### 3. الأدمن (Admin)
- إدارة المستخدمين
- إدارة جميع الكتب
- إدارة طلبات شحن المحفظة
- عرض إحصائيات شاملة

## 🛠️ التقنيات المستخدمة

- **Backend**: Laravel 11
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Notifications**: Firebase Cloud Messaging (FCM)
- **Mobile**: Flutter (قيد التطوير)
- **Web Admin**: React (قيد التطوير)

## 🚀 البدء السريع

### المتطلبات
- PHP >= 8.2
- Composer
- MySQL/MariaDB

### التثبيت

```bash
# 1. تثبيت dependencies
composer install

# 2. إعداد البيئة
cp .env.example .env
php artisan key:generate

# 3. ضبط قاعدة البيانات في .env
# DB_DATABASE=librax
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 4. تشغيل migrations و seeders
php artisan migrate
php artisan db:seed

# 5. ربط storage
php artisan storage:link

# 6. تشغيل المشروع
php artisan serve
```

## 📱 API Endpoints

### Home & Books
- `GET /api/home` - بيانات الصفحة الرئيسية
- `GET /api/home/search` - البحث عن كتب
- `GET /api/home/filter` - فلترة الكتب
- `GET /api/categories` - جميع الأصناف
- `GET /api/books/{id}` - تفاصيل كتاب
- `GET /api/books/category/{id}` - كتب حسب الصنف

### Favorites
- `GET /api/favorites` - المفضلة
- `POST /api/favorites` - إضافة للمفضلة
- `POST /api/favorites/toggle` - تبديل المفضلة
- `DELETE /api/favorites/{bookId}` - إزالة من المفضلة

### Notifications
- `GET /api/notifications` - الإشعارات
- `GET /api/notifications/unread-count` - عدد غير المقروءة
- `POST /api/notifications/{id}/read` - تعليم كمقروء
- `DELETE /api/notifications/{id}` - حذف إشعار

## 📚 التوثيق

- **[QUICK_START.md](QUICK_START.md)** - دليل البدء السريع
- **[SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md)** - تعليمات التثبيت المفصلة
- **[API_DOCUMENTATION_HOME.md](API_DOCUMENTATION_HOME.md)** - توثيق كامل للـ API
- **[Postman Collection](Librax_Home_API.postman_collection.json)** - مجموعة Postman للاختبار

## 🧪 حسابات تجريبية

بعد تشغيل `php artisan db:seed`:

| النوع | الهاتف | كلمة السر |
|------|---------|-----------|
| Admin | 0911111111 | admin123 |
| صاحب مكتبة | 0923456789 | password123 |
| زبون | 0934567890 | password123 |

## 📊 قاعدة البيانات

### الجداول الرئيسية
- `users` - المستخدمين (admin, library_owner, customer)
- `categories` - أصناف الكتب
- `books` - الكتب
- `favorites` - المفضلة
- `notifications` - الإشعارات
- `fcm_tokens` - رموز FCM للإشعارات

## ✅ ما تم إنجازه

### Phase 1: Home & Books ✅
- [x] Models & Migrations
- [x] Authentication System
- [x] Home API
- [x] Categories API
- [x] Books API
- [x] Favorites API
- [x] Notifications API
- [x] Search & Filter
- [x] API Documentation
- [x] Postman Collection
- [x] Demo Data Seeder

### Phase 2: Orders (قيد التطوير)
- [ ] Orders System
- [ ] Payment Methods
- [ ] Order Status Management
- [ ] Ratings & Reviews

### Phase 3: Wallet (قيد التطوير)
- [ ] Wallet System
- [ ] Charge Requests
- [ ] Transaction History

### Phase 4: Library Owner Dashboard (قيد التطوير)
- [ ] Books Management
- [ ] Orders Management
- [ ] Statistics Dashboard

### Phase 5: Admin Dashboard (قيد التطوير)
- [ ] Users Management
- [ ] Books Management
- [ ] Charge Requests Management
- [ ] System Statistics

## 🧪 الاختبار

### باستخدام Postman
استورد ملف `Librax_Home_API.postman_collection.json`

### باستخدام cURL
```bash
# تسجيل الدخول
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"phone":"0934567890","password":"password123"}'

# جلب بيانات Home
curl -X GET http://localhost:8000/api/home \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### باستخدام Bash Script
```bash
chmod +x TEST_API.sh
./TEST_API.sh
```

## 📁 هيكل المشروع

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── BookController.php
│   │   ├── CategoryController.php
│   │   ├── FavoriteController.php
│   │   └── NotificationController.php
│   ├── Resources/
│   │   ├── BookResource.php
│   │   ├── BookListResource.php
│   │   └── CategoryResource.php
│   └── Traits/
│       └── ResponseTrait.php
├── Models/
│   ├── User.php
│   ├── Book.php
│   ├── Category.php
│   ├── Favorite.php
│   └── FcmToken.php
└── Services/
    ├── OtpService.php
    ├── UserAuthService.php
    └── FirebaseNotificationService.php

database/
├── migrations/
│   ├── create_users_table.php
│   ├── create_categories_table.php
│   ├── create_books_table.php
│   ├── create_favorites_table.php
│   └── add_library_name_to_users_table.php
└── seeders/
    ├── CategorySeeder.php
    ├── DemoDataSeeder.php
    └── DatabaseSeeder.php
```

## 🔧 إعدادات إضافية

### Firebase (للإشعارات)
1. ضع ملف `firebase-credentials.json` في `storage/app/firebase/`
2. حدّث `.env`:
```env
FIREBASE_CREDENTIALS=firebase/firebase-credentials.json
```

### Storage
الملفات تُحفظ في:
```
storage/app/public/
├── avatars/
├── books/
│   ├── covers/
│   ├── pdfs/
│   └── audios/
└── categories/icons/
```

## 🤝 المساهمة

هذا مشروع جامعي. للمساهمة:
1. Fork المشروع
2. أنشئ branch جديد
3. Commit التغييرات
4. Push للـ branch
5. افتح Pull Request

## 📝 الترخيص

هذا المشروع للأغراض التعليمية.

## 📞 الدعم

للمساعدة أو الاستفسارات، راجع ملفات التوثيق أو افتح Issue.

---

**تم التطوير بواسطة**: فريق Librax  
**الجامعة**: [اسم الجامعة]  
**السنة**: 2026
