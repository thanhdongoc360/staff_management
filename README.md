# StaffHub - Hệ thống Quản lý Nhân viên

Hệ thống quản lý nhân viên xây dựng bằng Laravel 9 (Backend) và Vue.js 3 (Frontend).

Login as Admin: admin@staffhub.com / admin123
→ See Admin Dashboard with real employee data

Login as Employee: john@staffhub.com / employee123
→ See Employee Dashboard with personal stats

## 🚀 Phase 1: Setup & Cấu trúc dự án - HOÀN THÀNH ✅

### Công nghệ sử dụng

**Backend:**
- Laravel 9
- Laravel Sanctum (Authentication)
- MySQL Database
- PHP 8.0+

**Frontend:**
- Vue.js 3 (Composition API)
- Vue Router 4
- Pinia (State Management)
- Axios (HTTP Client)
- Bootstrap 5
- Bootstrap Icons
- Vite

## 📦 Cài đặt

### Prerequisites
- PHP 8.0 hoặc cao hơn
- Composer
- Node.js 16+ và NPM
- MySQL

### Backend Setup

1. Di chuyển vào thư mục backend:
```bash
cd backend
```

2. Cài đặt dependencies:
```bash
composer install
```

3. Sao chép file .env:
```bash
copy .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Cấu hình database trong file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=staffhub_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

6. Chạy migrations (sau khi hoàn thành Phase 2):
```bash
php artisan migrate
```

7. Chạy seeders (sau khi hoàn thành Phase 2):
```bash
php artisan db:seed
```

8. Khởi động server:
```bash
php artisan serve
```

Backend sẽ chạy tại: `http://localhost:8000`

### Frontend Setup

1. Di chuyển vào thư mục frontend:
```bash
cd frontend
```

2. Cài đặt dependencies:
```bash
npm install
```

3. Sao chép file .env:
```bash
copy .env.example .env
```

4. Cấu hình API URL trong file `.env`:
```env
VITE_API_URL=http://localhost:8000
VITE_APP_NAME=StaffHub
```

5. Khởi động dev server:
```bash
npm run dev
```

Frontend sẽ chạy tại: `http://localhost:5173`

## 📁 Cấu trúc dự án

### Backend Structure
```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API Controllers (Phase 2+)
│   │   ├── Middleware/      # Custom Middleware
│   │   └── Kernel.php       # ✅ Cấu hình Sanctum
│   ├── Models/              # Database Models (Phase 2)
│   └── Providers/
├── config/
│   ├── cors.php             # ✅ Cấu hình CORS
│   ├── sanctum.php          # ✅ Cấu hình Sanctum
│   └── database.php
├── database/
│   ├── migrations/          # Database migrations (Phase 2)
│   └── seeders/             # Database seeders (Phase 2)
└── routes/
    ├── api.php              # API routes (Phase 2+)
    └── web.php
```

### Frontend Structure
```
frontend/
├── src/
│   ├── assets/              # Static assets
│   ├── components/          # Vue components
│   │   └── layouts/         # Layout components
│   ├── router/              # ✅ Vue Router config
│   │   └── index.js         # ✅ Routes & navigation guards
│   ├── services/            # ✅ API services
│   │   ├── api.js           # ✅ Axios config
│   │   └── authService.js   # ✅ Authentication service
│   ├── stores/              # ✅ Pinia stores
│   │   └── auth.js          # ✅ Authentication store
│   ├── views/               # ✅ Page views
│   │   ├── LoginView.vue    # ✅ Login page
│   │   ├── DashboardView.vue
│   │   ├── EmployeesView.vue
│   │   ├── LeaveRequestsView.vue
│   │   ├── ProfileView.vue
│   │   ├── MyLeavesView.vue
│   │   ├── SalaryView.vue
│   │   └── NotificationsView.vue
│   ├── App.vue              # ✅ Root component
│   └── main.js              # ✅ App entry point
├── .env                     # ✅ Environment variables
└── vite.config.js
```

## ✅ Phase 1 - Hoàn thành

### Backend
- ✅ Cài đặt Laravel 9 với Sanctum
- ✅ Cấu hình database MySQL
- ✅ Cấu hình CORS cho API
- ✅ Enable Sanctum middleware
- ✅ Cấu hình .env.example

### Frontend
- ✅ Cài đặt Vue.js 3 với Vite
- ✅ Setup Vue Router với navigation guards
- ✅ Cài đặt và cấu hình Bootstrap 5
- ✅ Setup Axios với interceptors
- ✅ Cấu hình Pinia cho state management
- ✅ Tạo Authentication Store
- ✅ Tạo Auth Service
- ✅ Tạo Login View
- ✅ Tạo placeholder cho các views khác
- ✅ Cấu hình .env

## 🔐 Authentication Flow

1. User nhập credentials tại LoginView
2. Frontend gọi `/sanctum/csrf-cookie` để lấy CSRF token
3. Frontend gọi `/api/login` với credentials
4. Backend trả về token và user info
5. Token được lưu vào localStorage
6. Axios tự động thêm token vào header cho các request tiếp theo
7. Router guard kiểm tra authentication trước khi cho phép truy cập routes

## 🛣️ Routes đã cấu hình

| Path | Component | Auth Required | Role Required |
|------|-----------|---------------|---------------|
| `/login` | LoginView | ❌ | - |
| `/` | Redirect to Dashboard | ✅ | - |
| `/dashboard` | DashboardView | ✅ | - |
| `/employees` | EmployeesView | ✅ | Admin |
| `/leave-requests` | LeaveRequestsView | ✅ | - |
| `/profile` | ProfileView | ✅ | - |
| `/my-leaves` | MyLeavesView | ✅ | Employee |
| `/salary` | SalaryView | ✅ | - |
| `/notifications` | NotificationsView | ✅ | - |

## 📝 Lộ trình tiếp theo

### Phase 2: Database & Models (2-3 ngày)
- Thiết kế database schema
- Tạo migrations cho các bảng:
  - users
  - employees
  - leave_requests
  - salaries
  - notifications
  - schedules
- Tạo Models với relationships
- Tạo Seeders cho dữ liệu mẫu

### Phase 3: Authentication (2-3 ngày)
- Tạo API endpoints cho login/logout
- Implement Register API (optional)
- User authentication
- Middleware phân quyền
- Token management

### Phase 4-11: Xem file lộ trình chi tiết

## 🔧 Scripts hữu ích

### Backend
```bash
php artisan serve              # Chạy dev server
php artisan migrate            # Chạy migrations
php artisan migrate:fresh      # Reset database
php artisan db:seed            # Chạy seeders
php artisan route:list         # Xem danh sách routes
php artisan make:controller    # Tạo controller
php artisan make:model         # Tạo model
php artisan make:migration     # Tạo migration
```

### Frontend
```bash
npm run dev      # Chạy dev server
npm run build    # Build production
npm run preview  # Preview production build
```

## 🐛 Troubleshooting

### CORS Issues
- Kiểm tra `FRONTEND_URL` trong backend `.env`
- Kiểm tra `SANCTUM_STATEFUL_DOMAINS` trong backend `.env`
- Xác nhận `supports_credentials: true` trong `config/cors.php`

### Authentication Issues
- Xóa localStorage và thử login lại
- Kiểm tra Sanctum middleware đã được enable trong `app/Http/Kernel.php`
- Verify CSRF cookie có được set không

### Database Connection
- Kiểm tra MySQL service đang chạy
- Verify database credentials trong `.env`
- Tạo database nếu chưa có: `CREATE DATABASE staffhub_db;`

## 📚 Tài liệu tham khảo

- [Laravel 9 Documentation](https://laravel.com/docs/9.x)
- [Laravel Sanctum](https://laravel.com/docs/9.x/sanctum)
- [Vue.js 3 Documentation](https://vuejs.org/)
- [Vue Router](https://router.vuejs.org/)
- [Pinia](https://pinia.vuejs.org/)
- [Bootstrap 5](https://getbootstrap.com/docs/5.0/)

## 👥 Demo Accounts (Sau khi complete Phase 3)

**Admin:**
- Email: admin@staffhub.com
- Password: admin123

**Employee:**
- Email: john@staffhub.com
- Password: employee123

## 📄 License

This project is for educational purposes.
