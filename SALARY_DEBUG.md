# 🔧 DEBUGGING SALARY ENDPOINT

## Test Routes Status

### Chạy command:
```bash
php artisan route:list | findstr salary
```

### Kết quả:
```
GET|HEAD  api/my-salaries .................................... Api\SalaryController@mySalaries
GET|HEAD  api/salaries ............................................ Api\SalaryController@index  
POST      api/salaries ............................................ Api\SalaryController@store
PUT       api/salaries/{id} ...................................... Api\SalaryController@update  
DELETE    api/salaries/{id} ..................................... Api\SalaryController@destroy
```

✅ Routes đã đăng ký đúng

---

## Fix Applied

### File: `backend/routes/api.php` (Line 86-87)

**Trước (sai):**
```php
Route::middleware('role:employee')->get('/my-salaries', [SalaryController::class, 'mySalaries']);
// ❌ Nằm NGOÀI nhóm auth:sanctum → không có xác thực
```

**Sau (đúng):**
```php
// Đã di chuyển vào trong nhóm auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    // ... other routes ...
    
    // Employee: My Salaries
    Route::middleware('role:employee')->get('/my-salaries', [SalaryController::class, 'mySalaries']);
});
```

✅ Đã sửa - route bây giờ trong `auth:sanctum` middleware

---

## Database Data

### Seeder data (từ EmployeeSeeder.php):
- Tạo admin user: `admin@staffhub.com` / `admin123`
- Tạo 6 employees, trong đó:
  - **John Doe** (EMP-00006): `john@staffhub.com` / `employee123`
  - Có 3 bảng lương:
    - Tháng 11/2025: 15,000,000 + 2,000,000 bonus = 17,000,000 VND
    - Tháng 10/2025: 15,000,000 + 1,500,000 bonus = 16,500,000 VND
    - Tháng 9/2025:  15,000,000 + 2,500,000 bonus = 17,500,000 VND

✅ Dữ liệu test salary đã tồn tại

---

## Cách Test

### 1. Admin role (Tạo & xem tất cả lương):
```
URL: http://localhost:5173/salary
Login: admin@staffhub.com / admin123

Nên thấy:
- Bảng lương của TẤT CẢ nhân viên
- Nút "Thêm lương"
- Có dropdown filter theo nhân viên
```

### 2. Employee role (Chỉ xem lương của mình):
```
URL: http://localhost:5173/salary
Login: john@staffhub.com / employee123

Nên thấy:
- 3 bảng lương của John Doe:
  - Month 11/2025: 17,000,000 VND
  - Month 10/2025: 16,500,000 VND
  - Month 9/2025:  17,500,000 VND
- KHÔNG có nút "Thêm lương"
- KHÔNG có dropdown nhân viên (chỉ có tháng/năm)
```

---

## Troubleshooting

### Nếu vẫn không thấy dữ liệu:

1. **Clear cache:**
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

2. **Kiểm tra browser console:**
   - Mở DevTools (F12) → Console
   - Xem có error message nào không
   - Check Network tab → xem response từ `/api/my-salaries` hoặc `/api/salaries`

3. **Kiểm tra localStorage:**
   - DevTools → Application → LocalStorage
   - Xem `token` có tồn tại không
   - Xem `user` object có role đúng không

4. **Test API directly:**
```bash
# Lấy token trước:
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@staffhub.com","password":"employee123"}'

# Sau đó test endpoint (thay <token>):
curl -X GET http://localhost:8000/api/my-salaries \
  -H "Authorization: Bearer <token>"
```

---

## Kiểm tra Final

- ✅ Routes đã đúng
- ✅ Cache đã clear
- ✅ Dữ liệu test đã tồn tại
- ✅ Middleware `auth:sanctum` + `role:employee` đã áp dụng

**Nên hoạt động bình thường bây giờ!**
