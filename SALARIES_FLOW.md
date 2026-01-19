# 💰 Luồng hoạt động SALARIES - Frontend → Backend → Database

## 0. Mục tiêu
- Quản lý bảng lương: tạo / cập nhật / xoá (Admin), xem danh sách (Admin), xem lương của chính mình (Employee).
- Đảm bảo mỗi nhân viên chỉ có một bảng lương cho mỗi cặp tháng/năm.
- Phân quyền rõ ràng: Admin thao tác CRUD, Employee chỉ đọc lương của mình.

## 1. Kiến trúc tổng quan (7 lớp)
```
[Frontend View]
  └─ SalaryView (Vue) →
[Frontend Service]
  └─ salaryService (axios) →
[HTTP API]
  └─ /api/salaries (admin)
  └─ /api/my-salaries (employee) →
[Routes + Middleware]
  └─ auth:sanctum
  └─ role:admin (CRUD)
  └─ role:employee (my-salaries) →
[Controller]
  └─ SalaryController@index/store/update/destroy/mySalaries →
[Model]
  └─ Salary (belongsTo Employee → User) →
[Database]
  └─ salaries table (employee_id FK → employees → users)
```

## 2. Endpoint & Phân quyền
- Admin only: `GET /api/salaries`, `POST /api/salaries`, `PUT /api/salaries/{id}`, `DELETE /api/salaries/{id}`
- Employee only: `GET /api/my-salaries`
- Middleware: `auth:sanctum` + `role:admin` (CRUD), `role:employee` (my-salaries)

## 3. Frontend
- View: [frontend/src/views/SalaryView.vue](frontend/src/views/SalaryView.vue)
- Service: [frontend/src/services/salaryService.js](frontend/src/services/salaryService.js)

### 3.1 Danh sách (Admin xem tất cả hoặc lọc; Employee xem của mình)
- Admin UI: bộ lọc tháng/năm/nhân viên, bảng kết quả, nút Thêm/Sửa/Xoá.
- Employee UI: chỉ thấy bảng lương của chính mình, không có nút CRUD.
- Gọi hàm:
  - Admin: `salaryService.list({ page, per_page, month, year, employee_id })` → `GET /api/salaries`
  - Employee: `salaryService.mySalaries({ page, per_page, month, year })` → `GET /api/my-salaries`
- Hiển thị: tên nhân viên, mã, tháng/năm, lương cơ bản, thưởng, tổng, ghi chú.

### 3.2 Tạo/Sửa lương (Admin)
- Mở modal (`openCreate` hoặc `openEdit`).
- Form gồm: employee_id, month, year, base_salary, bonus, note.
- Lưu:
  - Nếu `editingId` có giá trị → `salaryService.update(id, payload)` → `PUT /api/salaries/{id}`
  - Nếu `editingId` null → `salaryService.create(payload)` → `POST /api/salaries`
- Sau khi lưu: đóng modal, gọi lại `loadData()` để refresh.

### 3.3 Xoá lương (Admin)
- Xác nhận trình duyệt → `salaryService.remove(id)` → `DELETE /api/salaries/{id}` → refresh.

## 4. Backend
- Routes: [backend/routes/api.php](backend/routes/api.php)
- Controller: [backend/app/Http/Controllers/Api/SalaryController.php](backend/app/Http/Controllers/Api/SalaryController.php)
- Model: [backend/app/Models/Salary.php](backend/app/Models/Salary.php)

### 4.1 Routes & Middleware
- `/api/salaries` prefix, `auth:sanctum` + `role:admin` → index, store, update, destroy.
- `/api/my-salaries` with `role:employee` → mySalaries.

### 4.2 Controller hành vi
1) **index (Admin - list)**
   - Eager load `employee.user`, sắp xếp `year desc`, `month desc`.
   - Filter tùy chọn: employee_id, month, year.
   - Paginate theo `per_page` (mặc định 10).
   - Trả về `data + meta` sau khi transform.

2) **store (Admin - create)**
   - Validate: employee_id exists, base_salary ≥ 0, bonus ≥ 0, month 1-12, year 2000-2100, note ≤ 255.
   - Chống trùng: kiểm tra tồn tại cùng `employee_id + month + year`; nếu có trả 422.
   - Tính `total = base_salary + bonus` (bonus mặc định 0).
   - Tạo bản ghi salaries.
   - Trả về 201 + data transform.

3) **update (Admin - update)**
   - Tìm salary by id.
   - Validate tương tự create (không đổi employee_id).
   - Cập nhật base_salary, bonus, total, month, year, note.
   - Trả về data transform.

4) **destroy (Admin - delete)**
   - Tìm salary by id, delete, trả message JSON.

5) **mySalaries (Employee - self view)**
   - Lấy employee_id từ `auth()->user()->employee`.
   - Nếu không có hồ sơ employee → 404.
   - Lọc optional month/year, paginate, trả `data + meta` transform.

### 4.3 Transform output
- `id, employee_id, employee_name, employee_code, base_salary, bonus, total, month, year, note, created_at (d/m/Y H:i)`

## 5. Database
- Bảng: `salaries`
- Cột chính: id, employee_id (FK), base_salary (decimal 2), bonus (decimal 2), total (decimal 2), month (int), year (int), note (nullable), timestamps.
- Quan hệ: Salary belongsTo Employee → Employee belongsTo User.
- Ràng buộc nghiệp vụ: một (employee_id, month, year) duy nhất (đang enforce bằng kiểm tra trong controller; nên thêm unique index nếu muốn cứng).

## 6. Luồng chi tiết theo thao tác

### 6.1 Admin xem danh sách (GET /api/salaries)
```
Vue SalaryView → salaryService.list(params)
  → GET /api/salaries?page=1&per_page=10&month=1&year=2026&employee_id=6
    Middleware: auth:sanctum ✅ → role:admin ✅
    Controller@index: filter + paginate + transform
  ← JSON data + meta
Vue render bảng lương với bộ lọc
```

### 6.2 Admin tạo bảng lương (POST /api/salaries)
```
Vue Modal (openCreate) nhập form → click Lưu
  → salaryService.create(payload)
    → POST /api/salaries
      Body: employee_id, base_salary, bonus?, month, year, note?
      Middleware: auth:sanctum ✅ → role:admin ✅
      Controller@store: validate → check trùng month/year → compute total → create → transform
    ← 201 + message + data
Vue đóng modal, loadData()
```

### 6.3 Admin sửa bảng lương (PUT /api/salaries/{id})
```
Vue Modal (openEdit) prefill → chỉnh sửa → Lưu
  → salaryService.update(id, payload)
    → PUT /api/salaries/{id}
      Middleware: auth:sanctum ✅ → role:admin ✅
      Controller@update: validate → compute total → update → transform
    ← message + data
Vue đóng modal, loadData()
```

### 6.4 Admin xoá bảng lương (DELETE /api/salaries/{id})
```
Vue confirm → salaryService.remove(id)
  → DELETE /api/salaries/{id}
    Middleware: auth:sanctum ✅ → role:admin ✅
    Controller@destroy: find → delete
  ← message
Vue refresh list
```

### 6.5 Employee xem lương của mình (GET /api/my-salaries)
```
Vue SalaryView (không phải admin) → salaryService.mySalaries(params)
  → GET /api/my-salaries?page=1&per_page=10&month=&year=2026
    Middleware: auth:sanctum ✅ → role:employee ✅
    Controller@mySalaries: lấy employee_id từ user → filter month/year → paginate → transform
  ← JSON data + meta
Vue render bảng lương cá nhân
```

## 7. Request / Response mẫu
- **Create (Admin)**
```http
POST /api/salaries
Authorization: Bearer <token>
Content-Type: application/json

{
  "employee_id": 6,
  "base_salary": 15000000,
  "bonus": 2000000,
  "month": 1,
  "year": 2026,
  "note": "Thưởng Tết"
}
```
Response 201 (rút gọn):
```json
{
  "message": "Tạo bảng lương thành công",
  "data": {
    "id": 10,
    "employee_id": 6,
    "employee_name": "Nguyễn Văn An",
    "employee_code": "EMP-00006",
    "base_salary": 15000000,
    "bonus": 2000000,
    "total": 17000000,
    "month": 1,
    "year": 2026,
    "note": "Thưởng Tết",
    "created_at": "08/01/2026 10:00"
  }
}
```

- **List (Admin)**
```http
GET /api/salaries?page=1&per_page=10&month=1&year=2026&employee_id=6
Authorization: Bearer <admin-token>
```
Response (rút gọn):
```json
{
  "data": [ { "id": 10, "employee_name": "Nguyễn Văn An", "total": 17000000, "month": 1, "year": 2026 } ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 10, "total": 1 }
}
```

- **My Salaries (Employee)**
```http
GET /api/my-salaries?year=2026
Authorization: Bearer <employee-token>
```

## 8. Validation & Business rules
- `employee_id` phải tồn tại trong `employees`.
- `month` 1–12; `year` 2000–2100.
- `base_salary ≥ 0`, `bonus ≥ 0` (nullable → default 0).
- Note tối đa 255 ký tự.
- Không cho phép duplicate (employee_id, month, year) trong controller (nên bổ sung unique index để cứng DB nếu cần).

## 9. Bảo mật & Phân quyền
- Tất cả endpoint đều yêu cầu `auth:sanctum`.
- Admin mới được CRUD `/api/salaries`.
- Employee chỉ được GET `/api/my-salaries` (dữ liệu ràng buộc theo employee_id của chính mình).

## 10. Gợi ý kiểm thử nhanh
1) Admin tạo lương tháng 1/2026 cho employee A → 201.
2) Admin tạo trùng tháng/năm/employee → 422 với message cảnh báo trùng.
3) Admin list filter theo employee_id, month, year → trả đúng một bản ghi vừa tạo.
4) Admin update bonus → total thay đổi đúng.
5) Admin delete → bản ghi biến mất khỏi list.
6) Employee đăng nhập → gọi `/api/my-salaries` chỉ thấy lương của chính mình, không thấy lương người khác.
