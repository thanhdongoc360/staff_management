# StaffHub Backend

Laravel 9 backend API cho hệ thống quản lý nhân viên StaffHub.

## 🚀 Quick Start

```bash
# Cài đặt dependencies
composer install

# Setup environment
copy .env.example .env

# Generate application key
php artisan key:generate

# Chạy migrations (sau Phase 2)
php artisan migrate

# Chạy seeders (sau Phase 2)
php artisan db:seed

# Khởi động server
php artisan serve
```

Server sẽ chạy tại: `http://localhost:8000`

## 🔧 Configuration

Cấu hình trong file `.env`:

```env
APP_NAME=StaffHub
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=staffhub_db
DB_USERNAME=root
DB_PASSWORD=your_password

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/        # API Controllers (Phase 3+)
│   │   └── Api/           # API Controllers namespace
│   ├── Middleware/        # Custom middleware
│   └── Kernel.php         # HTTP Kernel (Sanctum enabled)
├── Models/                # Eloquent Models (Phase 2)
└── Providers/             # Service Providers

config/
├── cors.php               # ✅ CORS configuration
├── sanctum.php            # ✅ Sanctum configuration
└── ...

database/
├── migrations/            # Database migrations (Phase 2)
├── seeders/              # Database seeders (Phase 2)
└── factories/            # Model factories (Phase 2)

routes/
├── api.php               # API routes (Phase 3+)
└── web.php              # Web routes
```

## 🔐 Authentication

Sử dụng **Laravel Sanctum** cho token-based authentication.

### Configuration
- ✅ Sanctum middleware enabled trong `app/Http/Kernel.php`
- ✅ CORS configured cho frontend domain
- ✅ Stateful domains configured

### API Endpoints (Phase 3)
```
POST   /api/login          # User login
POST   /api/logout         # User logout
GET    /api/user           # Get authenticated user
```

## 🗄️ Database Schema (Phase 2)

Tables sẽ được tạo:
- `users` - User accounts
- `employees` - Employee information
- `leave_requests` - Leave requests
- `salaries` - Salary records
- `notifications` - Notifications
- `schedules` - Work schedules

## 📝 API Routes Structure (Phase 3+)

```php
Route::middleware(['auth:sanctum'])->group(function () {
    // Protected routes here
});
```

## 🛠️ Development Commands

```bash
# Database
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Fresh migration
php artisan migrate:rollback     # Rollback migration
php artisan db:seed              # Run seeders

# Make commands
php artisan make:controller Api/EmployeeController --api
php artisan make:model Employee -m
php artisan make:migration create_employees_table
php artisan make:seeder EmployeeSeeder
php artisan make:request StoreEmployeeRequest

# Cache
php artisan config:cache         # Cache config
php artisan route:cache          # Cache routes
php artisan cache:clear          # Clear cache

# Other
php artisan route:list           # List all routes
php artisan tinker               # Laravel REPL
```

## 🔒 Middleware

### Global Middleware
- HandleCors - CORS handling
- TrustProxies - Proxy trust

### API Middleware Group
- EnsureFrontendRequestsAreStateful (Sanctum)
- throttle:api
- SubstituteBindings

## 📚 Models & Relationships (Phase 2)

```php
User
- hasOne: Employee
- hasMany: LeaveRequest, Notification

Employee
- belongsTo: User
- hasMany: LeaveRequest, Salary

LeaveRequest
- belongsTo: Employee

Salary
- belongsTo: Employee

Notification
- belongsTo: User
```

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter AuthenticationTest
```

## 🚧 Development Status

✅ Phase 1 Complete:
- Laravel 9 installed
- Sanctum configured
- CORS configured
- Database configured
- Environment setup

🔜 Next Phases:
- Phase 2: Database & Models
- Phase 3: Authentication APIs
- Phase 4+: Feature implementation

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>
