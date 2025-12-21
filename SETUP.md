# 🚀 Hướng dẫn Setup nhanh - Phase 1

## ✅ Đã hoàn thành

Phase 1 của dự án StaffHub đã được setup hoàn chỉnh với các thành phần:

### Backend (Laravel 9)
- ✅ Laravel Sanctum cho authentication
- ✅ CORS configuration
- ✅ Database configuration
- ✅ Environment setup (.env)

### Frontend (Vue.js 3)
- ✅ Vue Router với navigation guards
- ✅ Pinia store cho state management
- ✅ Axios cho API calls
- ✅ Bootstrap 5 UI framework
- ✅ Authentication flow
- ✅ All views structure

## 📝 Bước tiếp theo để chạy project

### 1. Setup Database

Tạo database MySQL:
```sql
CREATE DATABASE staffhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cập nhật thông tin database trong `backend/.env`:
```env
DB_DATABASE=staffhub_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. Chạy Backend

Mở terminal tại thư mục gốc:
```bash
cd backend
php artisan serve
```

Backend sẽ chạy tại: http://localhost:8000

### 3. Chạy Frontend

Mở terminal mới tại thư mục gốc:
```bash
cd frontend
npm run dev
```

Frontend sẽ chạy tại: http://localhost:5173

### 4. Test Setup

Mở browser và truy cập: http://localhost:5173

Bạn sẽ thấy trang login của StaffHub!

## ⚠️ Lưu ý quan trọng

- Backend phải chạy ở port 8000
- Frontend phải chạy ở port 5173
- Hiện tại chưa có API endpoints (sẽ làm ở Phase 3)
- Database chưa có tables (sẽ làm ở Phase 2)

## 🔜 Phase 2: Database & Models

Tiếp theo bạn cần:
1. Thiết kế database schema chi tiết
2. Tạo migrations cho các bảng
3. Tạo Models với relationships
4. Tạo Seeders cho dữ liệu demo

Xem chi tiết trong file [README.md](README.md)

## 📁 Cấu trúc Project

```
StaffHub_GR2/
├── backend/              # Laravel 9 API
│   ├── app/
│   ├── config/          # ✅ Đã cấu hình
│   ├── database/        # Sẽ làm Phase 2
│   ├── routes/          # Sẽ làm Phase 3
│   └── .env             # ✅ Đã tạo
├── frontend/            # Vue.js 3
│   ├── src/
│   │   ├── router/      # ✅ Đã setup
│   │   ├── stores/      # ✅ Đã setup
│   │   ├── services/    # ✅ Đã setup
│   │   └── views/       # ✅ Đã tạo
│   └── .env             # ✅ Đã tạo
└── README.md            # ✅ Hướng dẫn chi tiết
```

## 💡 Tips

- Đọc file README.md trong từng thư mục backend/frontend để biết thêm chi tiết
- Các file cấu hình quan trọng đã được setup sẵn
- Login view đã được thiết kế theo UI mẫu
- Routing và authentication flow đã sẵn sàng

## 🐛 Troubleshooting

**Backend không chạy được:**
- Kiểm tra PHP version: `php -v` (cần >= 8.0)
- Kiểm tra Composer: `composer --version`
- Chạy: `composer install`

**Frontend không chạy được:**
- Kiểm tra Node version: `node -v` (cần >= 16)
- Chạy: `npm install`

**Database connection error:**
- Kiểm tra MySQL service đang chạy
- Verify database credentials trong .env
- Tạo database nếu chưa có

## 📞 Support

Nếu gặp vấn đề, kiểm tra:
1. README.md trong thư mục gốc
2. backend/README.md
3. frontend/README.md

Tất cả đã có hướng dẫn chi tiết!
