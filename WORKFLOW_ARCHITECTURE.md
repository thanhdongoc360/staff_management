# 🏗️ Luồng Hoạt động: Frontend → Backend → Database

## 📊 Kiến trúc tổng quát

```
┌──────────────────────────────────────────────────────────────────┐
│                        FRONTEND (Vue.js)                         │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │  Views (SalaryView, EmployeesView, etc.)                  │  │
│ │  - UI Components                                           │  │
│ │  - User Interactions                                       │  │
│ └─────────────────────────┬──────────────────────────────────┘  │
│                           │                                      │
│ ┌─────────────────────────▼──────────────────────────────────┐  │
│ │  Services (salaryService, employeeService, etc.)          │  │
│ │  - API calls (HTTP methods)                               │  │
│ │  - Business logic                                          │  │
│ │  - Data transformation                                    │  │
│ └─────────────────────────┬──────────────────────────────────┘  │
│                           │                                      │
│ ┌─────────────────────────▼──────────────────────────────────┐  │
│ │  API Client (axios)                                        │  │
│ │  - Request/Response Interceptors                           │  │
│ │  - Token management                                        │  │
│ │  - Error handling                                          │  │
│ └─────────────────────────┬──────────────────────────────────┘  │
└─────────────────────────────┼──────────────────────────────────┘
                              │
                    HTTP (RESTful API)
                    PUT/POST/GET/DELETE
                              │
┌─────────────────────────────▼──────────────────────────────────┐
│                      BACKEND (Laravel)                          │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │  Routes (api.php)                                          │ │
│ │  - Define endpoints                                        │ │
│ │  - Map to controllers                                      │ │
│ │  - Apply middleware (auth, role)                           │ │
│ └─────────────────────────┬─────────────────────────────────┘ │
│                           │                                    │
│ ┌─────────────────────────▼─────────────────────────────────┐ │
│ │  Controllers (SalaryController, etc.)                      │ │
│ │  - Handle HTTP requests                                    │ │
│ │  - Validate input                                          │ │
│ │  - Call models/services                                    │ │
│ │  - Return JSON response                                    │ │
│ └─────────────────────────┬─────────────────────────────────┘ │
│                           │                                    │
│ ┌─────────────────────────▼─────────────────────────────────┐ │
│ │  Models (Salary, Employee, User, etc.)                    │ │
│ │  - Eloquent ORM                                            │ │
│ │  - Define table schema                                     │ │
│ │  - Relationships (hasMany, belongsTo)                      │ │
│ │  - Casts (type conversion)                                 │ │
│ └─────────────────────────┬─────────────────────────────────┘ │
│                           │                                    │
│ ┌─────────────────────────▼─────────────────────────────────┐ │
│ │  Database Layer                                            │ │
│ │  - Query Builder                                           │ │
│ │  - SQL execution                                           │ │
│ └─────────────────────────┬─────────────────────────────────┘ │
└─────────────────────────────┼──────────────────────────────────┘
                              │
┌─────────────────────────────▼──────────────────────────────────┐
│                    DATABASE (MySQL)                            │
│  - users table                                                 │
│  - employees table                                             │
│  - salaries table                                              │
│  - leave_requests table                                        │
│  - schedules table                                             │
│  - notifications table                                         │
└────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Ví dụ thực tế: Tạo bảng lương (Salary)

### 1️⃣ **FRONTEND - User Interface**

**File:** `frontend/src/views/SalaryView.vue`

```vue
<template>
  <div class="salary-form">
    <form @submit.prevent="createSalary">
      <input v-model="form.employee_id" type="number" placeholder="Employee ID">
      <input v-model="form.base_salary" type="number" placeholder="Base Salary">
      <input v-model="form.bonus" type="number" placeholder="Bonus">
      <select v-model="form.month">
        <option v-for="m in 12" :value="m">{{ m }}</option>
      </select>
      <input v-model="form.year" type="number" placeholder="Year">
      <button type="submit">Create Salary</button>
    </form>
  </div>
</template>

<script>
import { salaryService } from '@/services/salaryService'

export default {
  data() {
    return {
      form: {
        employee_id: null,
        base_salary: 0,
        bonus: 0,
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear()
      }
    }
  },
  methods: {
    async createSalary() {
      try {
        const response = await salaryService.create(this.form) // 👈 Call service
        console.log('Salary created:', response)
      } catch (error) {
        console.error('Error:', error)
      }
    }
  }
}
</script>
```

**Người dùng:** Click "Create Salary" button ↓

---

### 2️⃣ **FRONTEND - Service Layer**

**File:** `frontend/src/services/salaryService.js`

```javascript
import apiClient from './api'

export const salaryService = {
  async create(payload) {
    // payload = { employee_id: 1, base_salary: 5000, bonus: 500, month: 1, year: 2026 }
    
    const response = await apiClient.post('/api/salaries', payload)
    //                           ↑ HTTP POST request
    //                           ↓ baseURL + endpoint
    // http://localhost:8000/api/salaries
    
    return response.data
  }
}
```

**Gửi HTTP request:** 
```
POST http://localhost:8000/api/salaries
Headers: {
  Authorization: "Bearer <token>",
  Content-Type: "application/json"
}
Body: {
  "employee_id": 1,
  "base_salary": 5000,
  "bonus": 500,
  "month": 1,
  "year": 2026
}
```

↓

---

### 3️⃣ **BACKEND - Routes**

**File:** `backend/routes/api.php`

```php
// Match POST /api/salaries
Route::middleware('role:admin')->prefix('salaries')->group(function () {
    Route::post('/', [SalaryController::class, 'store']);
    //       ↑ Matches POST /api/salaries
    //       ↓ Calls SalaryController@store method
});
```

**Middleware check:**
- ✅ User authenticated? (via `auth:sanctum`)
- ✅ User role is 'admin'? (via `role:admin`)

↓

---

### 4️⃣ **BACKEND - Controller**

**File:** `backend/app/Http/Controllers/Api/SalaryController.php`

```php
public function store(Request $request)
{
    // Validate input
    $validated = $request->validate([
        'employee_id' => ['required', 'exists:employees,id'],
        'base_salary' => ['required', 'numeric', 'min:0'],
        'bonus' => ['nullable', 'numeric', 'min:0'],
        'month' => ['required', 'integer', 'between:1,12'],
        'year' => ['required', 'integer', 'between:2000,2100'],
        'note' => ['nullable', 'string', 'max:255'],
    ]);
    
    // Check if salary already exists for month/year
    $exists = Salary::where('employee_id', $validated['employee_id'])
        ->where('month', $validated['month'])
        ->where('year', $validated['year'])
        ->exists();
    
    if ($exists) {
        return response()->json(
            ['message' => 'Đã có bảng lương cho tháng/năm này'], 
            422
        );
    }
    
    // Calculate total
    $total = $validated['base_salary'] + ($validated['bonus'] ?? 0);
    
    // Create record via Model (↓ interact with database)
    $salary = Salary::create([
        'employee_id' => $validated['employee_id'],
        'base_salary' => $validated['base_salary'],
        'bonus' => $validated['bonus'] ?? 0,
        'total' => $total,
        'month' => $validated['month'],
        'year' => $validated['year'],
        'note' => $validated['note'] ?? null,
    ]);
    
    // Return JSON response
    return response()->json([
        'message' => 'Tạo bảng lương thành công',
        'data' => $this->transform($salary->load('employee.user')),
    ], 201);
}
```

↓

---

### 5️⃣ **BACKEND - Model**

**File:** `backend/app/Models/Salary.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'base_salary',
        'bonus',
        'total',
        'month',
        'year',
        'note',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',    // Cast to decimal
        'bonus' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationship: Salary belongsTo Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
```

**Eloquent ORM:** Tự động tạo SQL INSERT statement ↓

---

### 6️⃣ **DATABASE - Insert**

**Salary Model → MySQL Table**

```sql
INSERT INTO salaries 
  (employee_id, base_salary, bonus, total, month, year, note, created_at, updated_at)
VALUES 
  (1, 5000.00, 500.00, 5500.00, 1, 2026, NULL, 2026-01-02 10:30:45, 2026-01-02 10:30:45)
```

**Database Structure:**

```
┌─────────────────────────────────────┐
│ salaries table                      │
├─────────────────────────────────────┤
│ id: 1                               │
│ employee_id: 1 (FK → employees)     │
│ base_salary: 5000.00                │
│ bonus: 500.00                       │
│ total: 5500.00                      │
│ month: 1                            │
│ year: 2026                          │
│ note: NULL                          │
│ created_at: 2026-01-02 10:30:45    │
│ updated_at: 2026-01-02 10:30:45    │
└─────────────────────────────────────┘
```

↓ ✅ Success!

---

### 7️⃣ **RESPONSE - Back to Frontend**

```json
{
  "message": "Tạo bảng lương thành công",
  "data": {
    "id": 1,
    "employee_id": 1,
    "base_salary": "5000.00",
    "bonus": "500.00",
    "total": "5500.00",
    "month": 1,
    "year": 2026,
    "note": null,
    "employee": {
      "id": 1,
      "employee_code": "EMP001",
      "position": "Developer",
      "department": "IT",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  }
}
```

**Frontend receives response** → Update UI → Show success message ✅

---

## 🔍 Chi tiết các phần chính

### **Frontend - Services (6 files)**

| Service | Chức năng |
|---------|----------|
| `api.js` | Cấu hình axios, interceptors (token, error handling) |
| `authService.js` | login, register, logout, check user |
| `employeesService.js` | CRUD employees (admin only) |
| `salaryService.js` | Quản lý lương, mySalaries (employee) |
| `leaveRequestsService.js` | Tạo đơn, duyệt đơn |
| `notificationsService.js` | Lấy thông báo, mark as read |
| `profileService.js` | Profile user, change password |
| `dashboardService.js` | Dashboard stats |

### **Backend - API Routes (11 endpoints)**

| Method | Endpoint | Controller | Role | Chức năng |
|--------|----------|-----------|------|----------|
| POST | `/login` | AuthController | Public | Đăng nhập |
| POST | `/register` | AuthController | Public | Đăng ký |
| POST | `/api/salaries` | SalaryController | Admin | Tạo bảng lương |
| GET | `/api/salaries` | SalaryController | Admin | Danh sách lương |
| PUT | `/api/salaries/{id}` | SalaryController | Admin | Cập nhật lương |
| DELETE | `/api/salaries/{id}` | SalaryController | Admin | Xóa lương |
| GET | `/api/my-salaries` | SalaryController | Employee | Lương của tôi |
| GET | `/api/employees` | EmployeeController | Admin | Danh sách nhân viên |
| POST | `/api/employees` | EmployeeController | Admin | Tạo nhân viên |
| POST | `/api/my-leaves` | LeaveRequestController | Employee | Tạo đơn nghỉ |
| GET | `/api/my-leaves` | LeaveRequestController | Employee | Đơn nghỉ của tôi |

### **Backend - Models (6 bảng)**

```
users → employees → salaries
                  → schedules
                  → leave_requests
      → notifications
```

---

## 📝 Luồng dữ liệu cụ thể

### 1️⃣ **Lấy danh sách lương (GET)**

```
Frontend Form
    ↓
SalaryView: fetchSalaries()
    ↓
salaryService.list({ month: 1, year: 2026 })
    ↓
GET /api/salaries?month=1&year=2026
    ↓
SalaryController.index()
    - Query: Salary::with('employee.user')
           ->where('month', 1)
           ->where('year', 2026)
           ->paginate(10)
    ↓
SELECT * FROM salaries 
WHERE month = 1 AND year = 2026
LIMIT 10
    ↓
Return JSON with salaries
    ↓
Frontend: Update UI with data
```

### 2️⃣ **Cập nhật lương (PUT)**

```
Frontend Form: Edit salary
    ↓
SalaryView: updateSalary(id, data)
    ↓
salaryService.update(id, { base_salary: 6000 })
    ↓
PUT /api/salaries/{id}
    ↓
SalaryController.update()
    - Validate data
    - Salary::find(id)->update(data)
    ↓
UPDATE salaries 
SET base_salary = 6000, total = 6500, updated_at = NOW()
WHERE id = 1
    ↓
Return updated salary
    ↓
Frontend: Show success toast
```

### 3️⃣ **Xóa lương (DELETE)**

```
Frontend: Click delete button
    ↓
SalaryView: deleteSalary(id)
    ↓
salaryService.remove(id)
    ↓
DELETE /api/salaries/{id}
    ↓
SalaryController.destroy()
    - Salary::destroy(id)
    ↓
DELETE FROM salaries WHERE id = 1
    ↓
Return success message
    ↓
Frontend: Remove row from table
```

---

## 🔐 Bảo mật & Middleware

```
Frontend
    ↓ (gửi token)
api.js (Interceptor)
    ↓ Thêm Authorization header
Request
    ↓
Backend Routes
    ↓ Kiểm tra middleware
auth:sanctum (Xác thực token)
    ↓
role:admin (Kiểm tra role)
    ↓ ✅ Pass
Controller
    ↓
Database
```

---

## 💾 Database Flow

```
Controller (Salary::create($data))
    ↓
Eloquent ORM (Model)
    ↓
Query Builder (INSERT statement)
    ↓
PDO / Database Connection
    ↓
MySQL Engine
    ↓
Write to Disk
    ↓
Confirm with inserted ID
    ↓
Return to Controller
```

---

## 📊 Ví dụ Relationships

**Khi lấy lương kèm employee:**

```php
$salary = Salary::with('employee.user')->find(1);

// SQL: 
// SELECT * FROM salaries WHERE id = 1
// SELECT * FROM employees WHERE id = salaries.employee_id
// SELECT * FROM users WHERE id = employees.user_id

// Result:
{
  "id": 1,
  "employee_id": 1,
  "base_salary": 5000,
  "employee": {
    "id": 1,
    "position": "Developer",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

## 🎯 Tóm tắt

| Layer | Công việc | Ví dụ |
|-------|----------|-------|
| **Frontend** | UI, gọi API | SalaryView.vue → salaryService.js |
| **Axios** | HTTP client, token | api.js interceptors |
| **Backend Routes** | Định tuyến request | api.php (POST /api/salaries) |
| **Controller** | Xử lý logic | SalaryController.store() |
| **Model** | ORM, mapping table | Salary model |
| **Database** | Lưu dữ liệu | MySQL salaries table |

**Tất cả kết nối qua HTTP RESTful API (JSON)!**
