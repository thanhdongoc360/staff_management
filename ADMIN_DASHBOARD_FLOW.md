# 📊 Luồng hoạt động ADMIN DASHBOARD - Frontend → Backend → Database

## 📈 Sơ đồ tổng quát

```
┌───────────────────────────────────────────────────────────────┐
│                    ADMIN USER                                 │
│  Mở dashboard (/dashboard) với role: admin                    │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. DashboardView.vue (Admin section)                          │
│    - onMounted() → loadAdminDashboard()                       │
│    - Call multiple services in parallel                       │
│                          ↓                                    │
│ 2. Dashboard Service (dashboardService)                       │
│    - getAdminStats() → GET /api/admin/dashboard/stats         │
│    - getRecentEmployees() → GET /api/admin/dashboard/...      │
│    - getPendingLeaves() → GET /api/admin/dashboard/...        │
│    - getEmployeeStats() → GET /api/admin/dashboard/...        │
│                          ↓                                    │
│    Also calls:                                                │
│    - employeesService.getEmployees()                          │
│    - leaveRequestsService.list()                              │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Requests (Bearer token)
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 3. Routes (api.php) - auth:sanctum + role:admin               │
│    - GET /api/admin/dashboard/stats                           │
│    - GET /api/admin/dashboard/recent-employees                │
│    - GET /api/admin/dashboard/pending-leaves                  │
│    - GET /api/admin/dashboard/employee-stats                  │
│                          ↓                                    │
│ 4. AdminDashboardController                                   │
│    - stats(): aggregate counts from multiple tables           │
│    - recentEmployees(): JOIN employees + users, latest 5      │
│    - pendingLeaves(): WHERE status='Chờ duyệt'                │
│    - employeeStats(): GROUP BY status & department            │
│                          ↓                                    │
│ 5. Models (Eloquent relationships)                            │
│    - Employee (with User relationship)                        │
│    - LeaveRequest (with Employee → User)                      │
│    - User, Notification                                       │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 6. Multi-table queries:                                       │
│    - COUNT from employees table                               │
│    - COUNT from leave_requests WHERE status='Chờ duyệt'       │
│    - COUNT from notifications WHERE is_read=false             │
│    - SELECT from employees + users (JOIN)                     │
│    - GROUP BY status, department                              │
│                          ↓                                    │
│ 7. Tables involved:                                           │
│    - employees, users, leave_requests, notifications          │
└───────────────────────────────────────────────────────────────┘
                          ↓
            ✅ Frontend displays admin dashboard
           (4 stat cards + recent employees list)
```

---

## 🔄 Chi tiết từng luồng

### 1️⃣ **Lấy thống kê dashboard (GET /api/admin/dashboard/stats)**

#### **Frontend - Dashboard Component**

**File:** `frontend/src/views/DashboardView.vue`

```vue
<template>
  <!-- Admin Dashboard -->
  <div v-if="authStore.isAdmin">
    <h2 class="mb-4">Bảng điều khiển</h2>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <!-- Total Employees -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center" 
                   style="width: 56px; height: 56px; background: #E3F2FD; border-radius: 12px;">
                <i class="bi bi-people fs-3 text-primary"></i>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1" style="font-size: 14px;">Tổng nhân viên</h6>
              <!-- 👇 Display from stats -->
              <h3 class="mb-0 fw-bold">{{ stats.totalEmployees }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Leave Requests -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center" 
                   style="width: 56px; height: 56px; background: #FFF3E0; border-radius: 12px;">
                <i class="bi bi-file-earmark-text fs-3 text-warning"></i>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1" style="font-size: 14px;">Đơn nghỉ chờ duyệt</h6>
              <!-- 👇 Display from stats -->
              <h3 class="mb-0 fw-bold">{{ stats.pendingLeaves }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Unread Notifications -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center" 
                   style="width: 56px; height: 56px; background: #F3E8FF; border-radius: 12px;">
                <i class="bi bi-bell fs-3 text-primary"></i>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1" style="font-size: 14px;">Thông báo chưa đọc</h6>
              <h3 class="mb-0 fw-bold">{{ stats.unreadNotifications }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { dashboardService } from '../services/dashboardService'

const authStore = useAuthStore()

const stats = ref({
  totalEmployees: 0,
  pendingLeaves: 0,
  unreadNotifications: 0
})

onMounted(() => {
  loadAdminDashboard()
})

const loadAdminDashboard = async () => {
  try {
    const [employeesData, dashboardStats] = await Promise.all([
      employeesService.getEmployees({ page: 1, per_page: 5 }),
      dashboardService.getAdminStats(),
    ])

    stats.value = {
      totalEmployees: dashboardStats.total_employees,
      pendingLeaves: dashboardStats.pending_leaves,
      unreadNotifications: dashboardStats.unread_notifications
    }
  } catch (error) {
    console.error('Failed to load admin dashboard:', error)
  }
}
</script>
```

**User opens /dashboard** → Trigger `onMounted()` → `loadAdminDashboard()` ↓

---

#### **Frontend - Dashboard Service**

**File:** `frontend/src/services/dashboardService.js`

```javascript
import apiClient from './api'

export const dashboardService = {
  // 👇 Admin Dashboard Methods
  async getAdminStats() {
    try {
      // HTTP GET request to get all dashboard stats
      const response = await apiClient.get('/api/admin/dashboard/stats')
      return response.data  // {
                            //   total_employees: N,
                            //   pending_leaves: N,
                            //   active_staff: N,
                            //   total_notifications: N,
                            //   unread_notifications: N
                            // }
    } catch (error) {
      throw error
    }
  },

  async getRecentEmployees() {
    try {
      // HTTP GET request to get last 5 employees
      const response = await apiClient.get('/api/admin/dashboard/recent-employees')
      return response.data  // { data: [...], count: N }
    } catch (error) {
      throw error
    }
  },

  async getPendingLeaves() {
    try {
      // HTTP GET request to get pending leave requests
      const response = await apiClient.get('/api/admin/dashboard/pending-leaves')
      return response.data  // { data: [...], count: N }
    } catch (error) {
      throw error
    }
  },

  async getEmployeeStats() {
    try {
      // HTTP GET request to get stats grouped by status & department
      const response = await apiClient.get('/api/admin/dashboard/employee-stats')
      return response.data  // {
                            //   by_status: { "Đang làm việc": 5, "Thôi việc": 1 },
                            //   by_department: { "IT": 3, "HR": 2 }
                            // }
    } catch (error) {
      throw error
    }
  }
}
```

**HTTP Request:**

```http
GET http://localhost:8000/api/admin/dashboard/stats
Headers:
  Authorization: Bearer 1|adminToken123...
  Content-Type: application/json
```

↓

---

#### **Backend - Routes**

**File:** `backend/routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    // Admin Dashboard routes - ADMIN ONLY
    Route::middleware('role:admin')->prefix('admin/dashboard')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/recent-employees', [AdminDashboardController::class, 'recentEmployees']);
        Route::get('/pending-leaves', [AdminDashboardController::class, 'pendingLeaves']);
        Route::get('/employee-stats', [AdminDashboardController::class, 'employeeStats']);
    });
});
```

**Middleware checks:**
- ✅ User authenticated? (via `auth:sanctum`)
- ✅ User is admin? (via `role:admin`)

↓

---

#### **Backend - AdminDashboardController**

**File:** `backend/app/Http/Controllers/Api/AdminDashboardController.php`

```php
public function stats()
{
    // 1️⃣ Count total employees
    $totalEmployees = Employee::count();
    
    // 2️⃣ Count pending leave requests
    $pendingLeaves = LeaveRequest::where('status', 'Chờ duyệt')->count();
    
    // 3️⃣ Count active staff
    $activeStaff = Employee::where('status', 'Đang làm việc')->count();
    
    // 4️⃣ Count total and unread notifications
    $totalNotifications = auth()->user()->notifications()->count();
    $unreadNotifications = auth()->user()->notifications()->where('is_read', false)->count();

    // 5️⃣ Return aggregated stats
    return response()->json([
        'total_employees' => $totalEmployees,
        'pending_leaves' => $pendingLeaves,
        'active_staff' => $activeStaff,
        'total_notifications' => $totalNotifications,
        'unread_notifications' => $unreadNotifications,
    ], 200);
}
```

↓

---

#### **Backend - Database Queries**

**Query 1: Count total employees**

```sql
SELECT COUNT(*) as total_employees FROM employees
```

**Query 2: Count pending leave requests**

```sql
SELECT COUNT(*) as pending_leaves
FROM leave_requests
WHERE status = 'Chờ duyệt'
```

**Query 3: Count active staff**

```sql
SELECT COUNT(*) as active_staff
FROM employees
WHERE status = 'Đang làm việc'
```

**Query 4: Count notifications for current user**

```sql
SELECT COUNT(*) as total_notifications
FROM notifications
WHERE user_id = 1  -- authenticated admin
```

**Query 5: Count unread notifications**

```sql
SELECT COUNT(*) as unread_notifications
FROM notifications
WHERE user_id = 1 AND is_read = 0
```

**Database Tables:**

```
┌────────────────────────────────────────────────┐
│ employees table                                │
├────────────────────────────────────────────────┤
│ id, user_id, employee_code, position,         │
│ department, phone, status                      │
└────────────────────────────────────────────────┘

┌────────────────────────────────────────────────┐
│ leave_requests table                           │
├────────────────────────────────────────────────┤
│ id, employee_id, start_date, end_date, reason,│
│ type, status (Chờ duyệt|Đã duyệt|Từ chối)    │
└────────────────────────────────────────────────┘

┌────────────────────────────────────────────────┐
│ notifications table                            │
├────────────────────────────────────────────────┤
│ id, user_id, title, content, is_read, date    │
└────────────────────────────────────────────────┘
```

↓

---

#### **Backend - JSON Response**

```json
{
  "total_employees": 10,
  "pending_leaves": 2,
  "active_staff": 9,
  "total_notifications": 15,
  "unread_notifications": 3
}
```

↓

---

#### **Frontend - Display Stats**

```javascript
// Receive response
const response = await dashboardService.getAdminStats()

// Update state
stats.value = {
  totalEmployees: response.total_employees,      // 10
  pendingLeaves: response.pending_leaves,        // 2
  unreadNotifications: response.unread_notifications // 3
}

// Vue renders 3 stat cards with icons
```

---

### 2️⃣ **Lấy nhân viên gần đây (GET /api/admin/dashboard/recent-employees)**

#### **Frontend - Recent Employees List**

```vue
<template>
  <!-- Recent Employees -->
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Nhân viên gần đây</h5>
        <button class="btn btn-primary btn-sm" @click="viewAllEmployees">
          Xem tất cả nhân viên
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Họ và tên</th>
              <th>Vị trí</th>
              <th>Phòng ban</th>
              <th>Email</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <!-- 👇 Loop through recent employees -->
            <tr v-for="employee in recentEmployees" :key="employee.id">
              <td class="fw-semibold">{{ employee.name }}</td>
              <td>{{ employee.position }}</td>
              <td>{{ employee.department }}</td>
              <td>{{ employee.email }}</td>
              <td>
                <span class="badge bg-success-subtle text-success">
                  {{ employee.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
const recentEmployees = ref([])

const loadRecentEmployees = async () => {
  try {
    const res = await dashboardService.getRecentEmployees()
    recentEmployees.value = res.data || []
  } catch (error) {
    console.error('Failed to load recent employees:', error)
  }
}

onMounted(() => {
  loadRecentEmployees()
})
</script>
```

#### **Backend Controller**

```php
public function recentEmployees()
{
    // 1️⃣ Get last 5 employees with user relationship
    $employees = Employee::with('user')
        ->latest('created_at')
        ->limit(5)
        ->get()
        ->map(function ($employee) {
            // 2️⃣ Format response
            return [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
                'position' => $employee->position,
                'department' => $employee->department,
                'status' => $employee->status,
                'created_at' => $employee->created_at->format('d/m/Y'),
            ];
        });

    // 3️⃣ Return response
    return response()->json([
        'data' => $employees,
        'count' => $employees->count(),
    ], 200);
}
```

#### **SQL Query**

```sql
SELECT 
    e.id, e.employee_code, e.position, e.department, e.status, 
    u.name, u.email
FROM employees e
INNER JOIN users u ON e.user_id = u.id
ORDER BY e.created_at DESC
LIMIT 5
```

#### **Response**

```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "employee_code": "EMP-00001",
      "position": "Senior Developer",
      "department": "IT",
      "status": "Đang làm việc",
      "created_at": "05/01/2026"
    },
    {
      "id": 2,
      "name": "Jane Smith",
      "employee_code": "EMP-00002",
      "position": "HR Manager",
      "department": "HR",
      "status": "Đang làm việc",
      "created_at": "04/01/2026"
    }
  ],
  "count": 2
}
```

---

### 3️⃣ **Lấy đơn nghỉ phép chờ duyệt (GET /api/admin/dashboard/pending-leaves)**

#### **Backend Controller**

```php
public function pendingLeaves()
{
    // 1️⃣ Get all pending leave requests with relationships
    $leaves = LeaveRequest::with('employee.user')
        ->where('status', 'Chờ duyệt')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($leave) {
            // 2️⃣ Calculate number of days
            $days = $leave->start_date->diffInDays($leave->end_date) + 1;
            
            // 3️⃣ Format response
            return [
                'id' => $leave->id,
                'employee_name' => $leave->employee->user->name,
                'employee_code' => $leave->employee->employee_code,
                'start_date' => $leave->start_date->format('d/m/Y'),
                'end_date' => $leave->end_date->format('d/m/Y'),
                'reason' => $leave->reason,
                'type' => $leave->type,
                'days' => $days,
            ];
        });

    // 4️⃣ Return response
    return response()->json([
        'data' => $leaves,
        'count' => $leaves->count(),
    ], 200);
}
```

#### **SQL Query**

```sql
SELECT 
    lr.id, lr.start_date, lr.end_date, lr.reason, lr.type,
    e.id as employee_id, e.employee_code,
    u.name as employee_name
FROM leave_requests lr
INNER JOIN employees e ON lr.employee_id = e.id
INNER JOIN users u ON e.user_id = u.id
WHERE lr.status = 'Chờ duyệt'
ORDER BY lr.created_at DESC
```

#### **Response**

```json
{
  "data": [
    {
      "id": 1,
      "employee_name": "John Doe",
      "employee_code": "EMP-00001",
      "start_date": "10/01/2026",
      "end_date": "12/01/2026",
      "reason": "Family matters",
      "type": "Annual Leave",
      "days": 3
    },
    {
      "id": 2,
      "employee_name": "Jane Smith",
      "employee_code": "EMP-00002",
      "start_date": "15/01/2026",
      "end_date": "15/01/2026",
      "reason": "Doctor appointment",
      "type": "Personal Leave",
      "days": 1
    }
  ],
  "count": 2
}
```

---

### 4️⃣ **Lấy thống kê nhân viên (GET /api/admin/dashboard/employee-stats)**

#### **Backend Controller**

```php
public function employeeStats()
{
    // 1️⃣ Group employees by status
    $byStatus = Employee::selectRaw('status, count(*) as count')
        ->groupBy('status')
        ->get()
        ->keyBy('status')
        ->map(fn($item) => $item->count);

    // 2️⃣ Group employees by department
    $byDepartment = Employee::selectRaw('department, count(*) as count')
        ->groupBy('department')
        ->get()
        ->keyBy('department')
        ->map(fn($item) => $item->count);

    // 3️⃣ Return breakdown stats
    return response()->json([
        'by_status' => $byStatus,
        'by_department' => $byDepartment,
    ], 200);
}
```

#### **SQL Queries**

**Query 1: Group by status**

```sql
SELECT status, COUNT(*) as count
FROM employees
GROUP BY status
```

**Query 2: Group by department**

```sql
SELECT department, COUNT(*) as count
FROM employees
GROUP BY department
```

#### **Response**

```json
{
  "by_status": {
    "Đang làm việc": 9,
    "Thôi việc": 1,
    "Tạm dừng": 0
  },
  "by_department": {
    "IT": 5,
    "HR": 2,
    "Finance": 2,
    "Admin": 1
  }
}
```

**Usage for charts/analysis:**

```javascript
// Can be used to display pie charts or statistics
const statsByStatus = response.by_status
// {
//   "Đang làm việc": 9,
//   "Thôi việc": 1
// }

const statsByDepartment = response.by_department
// {
//   "IT": 5,
//   "HR": 2,
//   "Finance": 2,
//   "Admin": 1
// }
```

---

## 📋 Tóm tắt API Endpoints

| Method | Endpoint | Controller | Mục đích |
|--------|----------|-----------|----------|
| GET | `/api/admin/dashboard/stats` | `stats()` | Lấy 5 thống kê chính |
| GET | `/api/admin/dashboard/recent-employees` | `recentEmployees()` | Lấy 5 nhân viên mới nhất |
| GET | `/api/admin/dashboard/pending-leaves` | `pendingLeaves()` | Lấy đơn nghỉ chờ duyệt |
| GET | `/api/admin/dashboard/employee-stats` | `employeeStats()` | Thống kê theo status/dept |

---

## 🗄️ Database Schema

**Tables involved:**

```sql
/* employees table */
CREATE TABLE employees (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    employee_code VARCHAR(20) NOT NULL,
    position VARCHAR(255),
    department VARCHAR(255),
    phone VARCHAR(20),
    status VARCHAR(50),  -- 'Đang làm việc', 'Thôi việc'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_department (department),
    INDEX idx_created_at (created_at)
);

/* users table */
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'employee') DEFAULT 'employee'
);

/* leave_requests table */
CREATE TABLE leave_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    type VARCHAR(50),  -- 'Annual Leave', 'Personal Leave'
    status VARCHAR(50),  -- 'Chờ duyệt', 'Đã duyệt', 'Từ chối'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX idx_status (status),
    INDEX idx_employee_id (employee_id)
);

/* notifications table */
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255),
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);
```

---

## 🔐 Security & Authorization

### **Authentication Layer**
- All endpoints require `auth:sanctum` middleware
- Bearer token must be sent in Authorization header
- Token validated before any controller method executes

### **Authorization Layer - Admin Only**
- ✅ All admin dashboard endpoints are protected by `role:admin` middleware
- ✅ Only users with `role = 'admin'` can access
- ❌ Employees trying to access → 403 Forbidden

**Middleware Check:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('admin/dashboard')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        // ... only admins reach here
    });
});
```

### **Data Authorization**
- Endpoints aggregate data from **all employees** (not filtered by current user)
- This is intentional - admins should see complete organization stats
- No user_id filtering required (unlike employee endpoints)

---

## ✅ Tóm tắt luồng hoàn chỉnh

```
┌─────────────────────────────────────────────────────────────┐
│ ADMIN OPENS DASHBOARD                                       │
└─────────────────────────────────────────────────────────────┘

1. Component mounted
   ↓
2. Check: authStore.isAdmin ✅
   ↓
3. Call loadAdminDashboard()
   ↓
4. Parallel HTTP requests:
   ├─ GET /api/admin/dashboard/stats
   ├─ GET /api/admin/dashboard/recent-employees
   ├─ GET /api/admin/dashboard/pending-leaves
   └─ GET /api/admin/dashboard/employee-stats
   ↓
5. Each request:
   - Middleware: auth:sanctum ✅ + role:admin ✅
   - Execute controller method
   - Query database
   - Format & return JSON
   ↓
6. Database queries:
   ├─ SELECT COUNT(*) FROM employees
   ├─ SELECT COUNT(*) FROM leave_requests WHERE status='Chờ duyệt'
   ├─ SELECT e.*, u.name FROM employees JOIN users
   └─ SELECT status, COUNT(*) FROM employees GROUP BY status
   ↓
7. Responses aggregated in Vue state
   ↓
8. Vue renders:
   ├─ 3 stat cards (employees, pending leaves, projects)
   ├─ Recent employees table (5 rows)
   ├─ Pending leaves indicator (count)
   └─ Employee breakdown (by status & department)
   ↓
9. Admin can click:
   ├─ "Xem tất cả nhân viên" → navigate to /employees
   ├─ "Xem tất cả đơn" → navigate to /leave-requests
   └─ View detailed stats
   
   ✅ Dashboard fully loaded and interactive
```

---

## 🎯 Key Features

### **1. Real-time Stats**
- Stats cards show **live counts** from database
- Reflects latest employees, pending leaves, notifications

### **2. Recent Employees**
- Automatically shows **last 5 added employees**
- Sorted by `created_at DESC`
- Includes user details via relationship

### **3. Pending Leaves Table**
- Shows **only "Chờ duyệt" status**
- Admin can quickly see who's requesting leave
- Calculates days between start/end dates

### **4. Employee Analytics**
- Breakdown **by employment status**
  - Đang làm việc (Active)
  - Thôi việc (Resigned)
  - Tạm dừng (Suspended)
- Breakdown **by department**
  - IT, HR, Finance, Admin, etc.
- Useful for HR reporting

### **5. Notification Counts**
- Shows **total vs unread notifications**
- Admins can see their notification queue
- Quick notification management

---

## 🔍 Database Relationships Diagram

```
┌──────────────────────────────────────────────────────────┐
│                    users                                 │
│  id (PK) | name | email | role (admin|employee) | ...   │
└──────────────────┬───────────────────────────────────────┘
                   │ 1 (admin user)
                   │
                   ▼ 1
         ┌─────────────────────┐
         │   employees         │ (only for employees)
         │  user_id (FK) ◄─────┤
         │  position           │
         │  department         │
         │  status             │
         └────────┬────────────┘
                  │ 1
                  ├─────────────────────┬────────────────────┐
                  │ N                   │ N                  │
                  ▼                     ▼                    ▼
        ┌──────────────────┐   ┌──────────────────┐  ┌──────────────┐
        │ leave_requests   │   │   salaries       │  │ schedules    │
        │ employee_id (FK) │   │ employee_id (FK) │  │ employee_id  │
        │ status           │   │ amount           │  │ date         │
        │ start_date       │   │ month            │  │ time         │
        └──────────────────┘   └──────────────────┘  └──────────────┘
        
        
         ┌──────────────────────────────────┐
         │      notifications               │
         │  user_id (FK) ◄─────────────────┤
         │  title, content, is_read         │
         └──────────────────────────────────┘
```

**Admin Dashboard queries:**

1. **Stats** → COUNT from employees, leave_requests, notifications
2. **Recent Employees** → SELECT from employees + JOIN users
3. **Pending Leaves** → SELECT from leave_requests WHERE status='Chờ duyệt'
4. **Employee Stats** → GROUP BY status & department on employees

Toàn bộ quá trình đảm bảo **role-based access**, **efficient queries**, **aggregated reporting**, và **data security**! 🎯
