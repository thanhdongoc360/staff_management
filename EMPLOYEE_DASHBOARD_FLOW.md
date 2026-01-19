# 📊 Luồng hoạt động EMPLOYEE DASHBOARD - Frontend → Backend → Database

## 📈 Sơ đồ tổng quát

```
┌───────────────────────────────────────────────────────────────┐
│                     EMPLOYEE USER                             │
│  Mở dashboard (/dashboard) với role: employee                 │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. DashboardView.vue (Employee branch)                        │
│    - onMounted() → loadDashboardData()                        │
│    - Gọi nhiều service song song cho employee                 │
│                          ↓                                    │
│ 2. Services                                                   │
│    - profileService.getProfile()                              │
│    - leaveRequestsService.myList()                            │
│    - shiftService.getMyShifts()                               │
│    - scheduleService.getUpcomingSchedules()                   │
│    - dashboardService.getEmployeeStats() (nếu cần thêm stats) │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Requests (Bearer token)
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 3. Routes (api.php) - auth:sanctum + role:employee            │
│    - GET /api/employee/dashboard/stats                        │
│    - GET /api/employee/dashboard/my-leaves                    │
│    - GET /api/employee/dashboard/leave-stats                  │
│                          ↓                                    │
│ 4. EmployeeDashboardController                                │
│    - stats(): thống kê đơn nghỉ + thông báo + hồ sơ           │
│    - myLeaves(): lịch sử đơn nghỉ cá nhân                     │
│    - leaveStats(): nhóm theo trạng thái/loại đơn              │
│                          ↓                                    │
│ 5. Models (Eloquent relationships)                            │
│    - Employee (belongsTo User)                                │
│    - LeaveRequest (belongsTo Employee → User)                 │
│    - Notification                                             │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 6. Truy vấn chính:                                            │
│    - COUNT leave_requests theo status cho employee hiện tại   │
│    - COUNT notifications (total/unread) cho user hiện tại     │
│    - SELECT leave_requests theo employee_id                   │
│                          ↓                                    │
│ 7. Bảng liên quan:                                            │
│    - leave_requests, employees, users, notifications          │
└───────────────────────────────────────────────────────────────┘
                          ↓
            ✅ Frontend hiển thị dashboard nhân viên
           (thông tin cá nhân + lịch sử đơn nghỉ + lịch/ca)
```

---

## 🔄 Chi tiết từng luồng

### 1️⃣ Lấy thông tin + thống kê cơ bản (Employee branch)

#### Frontend - Dashboard Component

**File:** [frontend/src/views/DashboardView.vue](frontend/src/views/DashboardView.vue)

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import employeesService from '../services/employeesService'
import { leaveRequestsService } from '../services/leaveRequestsService'
import { profileService } from '../services/profileService'
import { dashboardService, scheduleService } from '../services/dashboardService'
import { shiftService } from '../services/shiftService'

const stats = ref({
  totalEmployees: 0,
  pendingLeaves: 0,
  unreadNotifications: 0
})

const employeeInfo = ref({
  name: '', email: '', position: '', department: '', status: 'Active'
})

const recentEmployees = ref([])
const leaveHistory = ref([])
const workSchedule = ref([])
const myShifts = ref([])

const loadAdminDashboard = async () => { /* ...admin branch... */ }

const loadEmployeeDashboard = async () => {
  try {
    const profile = await profileService.getProfile()
    employeeInfo.value = {
      name: profile.name || '',
      email: profile.email || '',
      position: profile.employee?.position || '',
      department: profile.employee?.department || '',
      status: profile.employee?.status || 'Active'
    }

    const leavesData = await leaveRequestsService.myList({ page: 1, per_page: 5 })
    leaveHistory.value = leavesData.data || []

    const response = await scheduleService.getUpcomingSchedules()
    workSchedule.value = response.data || []

    const shifts = await shiftService.getMyShifts()
    myShifts.value = shifts.data || []

    // Nếu cần số liệu tổng hợp: sử dụng dashboardService.getEmployeeStats()
    const statsRes = await dashboardService.getEmployeeStats()
    stats.value.unreadNotifications = statsRes.unread_notifications
  } catch (error) {
    console.error('Failed to load employee dashboard:', error)
  }
}

onMounted(() => {
  loadDashboardData()
})
</script>
```

**User mở /dashboard** → nhánh employee chạy `loadEmployeeDashboard()` → lấy hồ sơ + đơn nghỉ + ca + lịch + (tùy chọn) stats.

---

### 2️⃣ Lấy stats cho nhân viên (GET /api/employee/dashboard/stats)

#### Backend - Routes

**File:** [backend/routes/api.php](backend/routes/api.php#L120-L138)

```php
Route::middleware('auth:sanctum')->group(function () {
    // Employee Dashboard routes
    Route::middleware('role:employee')->prefix('employee/dashboard')->group(function () {
        Route::get('/stats', [EmployeeDashboardController::class, 'stats']);
        Route::get('/my-leaves', [EmployeeDashboardController::class, 'myLeaves']);
        Route::get('/leave-stats', [EmployeeDashboardController::class, 'leaveStats']);
    });
});
```

**Middleware checks:**
- ✅ Đăng nhập qua `auth:sanctum`
- ✅ Vai trò employee qua `role:employee`

#### Backend - Controller

**File:** [backend/app/Http/Controllers/Api/EmployeeDashboardController.php](backend/app/Http/Controllers/Api/EmployeeDashboardController.php#L1-L98)

```php
public function stats()
{
    $employee = auth()->user()->employee;
    if (!$employee) {
        return response()->json(['message' => 'Employee profile not found'], 404);
    }

    $pendingLeaves = LeaveRequest::where('employee_id', $employee->id)
        ->where('status', 'Chờ duyệt')
        ->count();
    
    $approvedLeaves = LeaveRequest::where('employee_id', $employee->id)
        ->where('status', 'Đã duyệt')
        ->count();

    $totalNotifications = auth()->user()->notifications()->count();
    $unreadNotifications = auth()->user()->notifications()->where('is_read', false)->count();

    return response()->json([
        'pending_leaves' => $pendingLeaves,
        'approved_leaves' => $approvedLeaves,
        'position' => $employee->position,
        'department' => $employee->department,
        'status' => $employee->status,
        'employee_code' => $employee->employee_code,
        'total_notifications' => $totalNotifications,
        'unread_notifications' => $unreadNotifications,
    ], 200);
}
```

#### Database queries (khái quát)

```sql
SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status = 'Chờ duyệt';
SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status = 'Đã duyệt';
SELECT COUNT(*) FROM notifications WHERE user_id = ?;
SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0;
```

#### JSON Response

```json
{
  "pending_leaves": 1,
  "approved_leaves": 3,
  "position": "Developer",
  "department": "IT",
  "status": "Đang làm việc",
  "employee_code": "EMP-00006",
  "total_notifications": 12,
  "unread_notifications": 2
}
```

---

### 3️⃣ Lấy lịch sử đơn nghỉ (GET /api/employee/dashboard/my-leaves)

#### Frontend (hiện tại dùng leaveRequestsService.myList)

```javascript
const leavesData = await leaveRequestsService.myList({ page: 1, per_page: 5 })
leaveHistory.value = leavesData.data || []
```

#### Backend - Controller

```php
public function myLeaves()
{
    $employee = auth()->user()->employee;
    if (!$employee) {
        return response()->json(['message' => 'Employee profile not found'], 404);
    }

    $leaves = LeaveRequest::where('employee_id', $employee->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($leave) {
            return [
                'id' => $leave->id,
                'start_date' => $leave->start_date->format('d/m/Y'),
                'end_date' => $leave->end_date->format('d/m/Y'),
                'reason' => $leave->reason,
                'type' => $leave->type,
                'status' => $leave->status,
                'days' => $leave->start_date->diffInDays($leave->end_date) + 1,
                'created_at' => $leave->created_at->format('d/m/Y H:i'),
            ];
        });

    return response()->json([
        'data' => $leaves,
        'count' => $leaves->count(),
    ], 200);
}
```

#### SQL Query

```sql
SELECT id, start_date, end_date, reason, type, status, created_at
FROM leave_requests
WHERE employee_id = ?
ORDER BY created_at DESC;
```

#### Response mẫu

```json
{
  "data": [
    {
      "id": 5,
      "start_date": "10/01/2026",
      "end_date": "12/01/2026",
      "reason": "Nghỉ phép năm",
      "type": "Vacation",
      "status": "Chờ duyệt",
      "days": 3,
      "created_at": "08/01/2026 10:30"
    }
  ],
  "count": 1
}
```

---

### 4️⃣ Thống kê đơn nghỉ theo trạng thái/loại (GET /api/employee/dashboard/leave-stats)

#### Backend - Controller

```php
public function leaveStats()
{
    $employee = auth()->user()->employee;
    if (!$employee) {
        return response()->json(['message' => 'Employee profile not found'], 404);
    }

    $byStatus = LeaveRequest::where('employee_id', $employee->id)
        ->selectRaw('status, count(*) as count')
        ->groupBy('status')
        ->get()
        ->keyBy('status')
        ->map(fn($item) => $item->count);

    $byType = LeaveRequest::where('employee_id', $employee->id)
        ->selectRaw('type, count(*) as count')
        ->groupBy('type')
        ->get()
        ->keyBy('type')
        ->map(fn($item) => $item->count);

    return response()->json([
        'by_status' => $byStatus,
        'by_type' => $byType,
    ], 200);
}
```

#### SQL Queries

```sql
SELECT status, COUNT(*) as count FROM leave_requests WHERE employee_id = ? GROUP BY status;
SELECT type,   COUNT(*) as count FROM leave_requests WHERE employee_id = ? GROUP BY type;
```

#### Response

```json
{
  "by_status": {
    "Chờ duyệt": 2,
    "Đã duyệt": 3,
    "Từ chối": 1
  },
  "by_type": {
    "Vacation": 3,
    "Sick": 2,
    "Personal": 1
  }
}
```

---

## 📋 Tóm tắt API Endpoints

| Method | Endpoint | Controller | Mục đích |
|--------|----------|-----------|----------|
| GET | `/api/employee/dashboard/stats` | `stats()` | Thống kê pending/approved + thông báo + hồ sơ |
| GET | `/api/employee/dashboard/my-leaves` | `myLeaves()` | Danh sách đơn nghỉ cá nhân |
| GET | `/api/employee/dashboard/leave-stats` | `leaveStats()` | Nhóm đơn nghỉ theo trạng thái/loại |

---

## 🗄️ Database Schema (liên quan)

```sql
/* leave_requests table */
CREATE TABLE leave_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    type VARCHAR(50),
    status VARCHAR(50), -- 'Chờ duyệt', 'Đã duyệt', 'Từ chối'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
);

/* employees table */
CREATE TABLE employees (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    employee_code VARCHAR(20) NOT NULL,
    position VARCHAR(255),
    department VARCHAR(255),
    phone VARCHAR(20),
    status VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
);

/* notifications table */
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255),
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);
```

---

## 🔐 Security & Authorization

- Tất cả endpoint dùng `auth:sanctum` + `role:employee`.
- Không cho phép truy vấn dữ liệu employee khác: mọi query luôn filter theo `employee_id` của user hiện tại.
- Nếu user chưa có hồ sơ employee → trả 404.

---

## ✅ Luồng hoàn chỉnh (Employee)

```
┌─────────────────────────────────────────────────────────────┐
│ EMPLOYEE OPENS DASHBOARD                                    │
└─────────────────────────────────────────────────────────────┘

1. Component mounted
   ↓
2. Check: authStore.isAdmin? ❌ → employee branch
   ↓
3. Gọi loadEmployeeDashboard()
   ↓
4. HTTP calls (song song/tuần tự):
   ├─ GET /api/profile → thông tin cá nhân (profileService)
   ├─ GET /api/my-leaves → lịch sử đơn nghỉ (leaveRequestsService)
   ├─ GET /api/schedules/upcoming → lịch sắp tới (scheduleService)
   ├─ GET /api/shift/my-shifts → ca làm việc (shiftService)
   └─ (Tùy chọn) GET /api/employee/dashboard/stats → thống kê tổng hợp
   ↓
5. Backend: middleware auth:sanctum ✅ + role:employee ✅
   - EmployeeDashboardController xử lý stats/myLeaves/leaveStats
   - Các controller profile/leaves/shifts xử lý tương ứng
   ↓
6. Database queries:
   - leave_requests filter employee_id + group/count
   - notifications đếm total/unread cho user
   - schedules/shifts theo user/employee
   ↓
7. Responses cập nhật state Vue
   ↓
8. Vue render:
   - Thẻ thông tin cá nhân
   - Bảng lịch sử đơn nghỉ + badge trạng thái
   - Danh sách lịch sắp tới + ca làm việc
   - (Nếu dùng) cards thống kê pending/approved/unread notifications
   ↓
9. Người dùng thao tác: xem hồ sơ, nộp đơn nghỉ (/my-leaves), xem lịch (/schedules)

   ✅ Dashboard nhân viên hiển thị đầy đủ dữ liệu cá nhân
```

---

## 🎯 Key Points

- Stats employee: đếm pending/approved leaves và unread notifications theo user hiện tại.
- My leaves: luôn lọc `employee_id` để không lộ dữ liệu người khác.
- leave-stats: cung cấp breakdown để vẽ chart nếu cần.
- Frontend hiện đang dùng profileService + leaveRequestsService; có thể bổ sung gọi stats để hiển thị thẻ tổng hợp.
