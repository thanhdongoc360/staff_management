# 📝 Luồng hoạt động LEAVE REQUESTS - Frontend → Backend → Database

## 📈 Sơ đồ tổng quát

```
┌───────────────────────────────────────────────────────────────┐
│                    ADMIN / EMPLOYEE                           │
│  Admin: /leave-requests (quản lý tất cả đơn)                  │
│  Employee: /my-leaves (tạo & xem đơn của mình)                │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. LeaveRequestsView.vue (Admin)                              │
│    - Danh sách tất cả đơn + filter theo status/type           │
│    - Nút Duyệt / Từ chối / Xóa                                │
│                                                               │
│ 2. MyLeavesView.vue (Employee)                                │
│    - Form tạo đơn mới (start_date, end_date, reason, type)    │
│    - Danh sách đơn của mình                                   │
│                          ↓                                    │
│ 3. Leave Requests Service (leaveRequestsService)              │
│    - list() → GET /api/leave-requests (admin)                 │
│    - myList() → GET /api/my-leaves (employee)                 │
│    - create() → POST /api/my-leaves (employee)                │
│    - approve() → POST /api/leave-requests/{id}/status         │
│    - reject() → POST /api/leave-requests/{id}/status          │
│    - delete() → DELETE /api/leave-requests/{id} (admin)       │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Requests (Bearer token)
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 4. Routes (api.php)                                           │
│    - GET /api/leave-requests (auth:sanctum + role:admin)      │
│    - POST /api/leave-requests/{id}/status (admin)             │
│    - DELETE /api/leave-requests/{id} (admin)                  │
│    - GET /api/my-leaves (auth:sanctum + role:employee)        │
│    - POST /api/my-leaves (employee create)                    │
│                          ↓                                    │
│ 5. LeaveRequestController                                     │
│    - index(): list tất cả (admin)                             │
│    - myLeaves(): list của employee hiện tại                   │
│    - store(): tạo đơn mới + notify admin                      │
│    - updateStatus(): duyệt/từ chối + notify employee         │
│    - destroy(): xóa đơn                                       │
│                          ↓                                    │
│ 6. Models (Eloquent)                                          │
│    - LeaveRequest (belongsTo Employee → User)                 │
│    - Employee (hasMany LeaveRequests)                         │
│    - Notification                                             │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 7. Multi-table operations:                                    │
│    - SELECT leave_requests JOIN employees JOIN users          │
│    - Filter theo status/type/employee_id                      │
│    - INSERT leave_requests (create đơn)                       │
│    - UPDATE leave_requests (cập nhật status)                  │
│    - DELETE leave_requests (xóa đơn)                          │
│    - INSERT notifications (gửi thông báo)                     │
│                          ↓                                    │
│ 8. Bảng liên quan:                                            │
│    - leave_requests, employees, users, notifications          │
└───────────────────────────────────────────────────────────────┘
                          ↓
            ✅ Admin/Employee quản lý đơn nghỉ
           (Duyệt/Từ chối/Tạo mới + thông báo)
```

---

## 🔄 Chi tiết từng luồng

### 1️⃣ Admin lấy danh sách đơn nghỉ (GET /api/leave-requests)

#### Frontend - Leave Requests Component

**File:** [frontend/src/views/LeaveRequestsView.vue](frontend/src/views/LeaveRequestsView.vue)

```vue
<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Quản lý đơn nghỉ phép</h2>
    </div>

    <!-- Filters -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <select v-model="filters.status" class="form-select" @change="fetchLeaveRequests()">
          <option value="">Tất cả trạng thái</option>
          <option value="Chờ duyệt">Chờ duyệt</option>
          <option value="Đã duyệt">Đã duyệt</option>
          <option value="Từ chối">Từ chối</option>
        </select>
      </div>
      <div class="col-md-4">
        <input v-model="filters.search" type="text" class="form-control" placeholder="Tìm kiếm tên/mã nhân viên..." @keyup.enter="fetchLeaveRequests()" />
      </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Nhân viên</th>
              <th>Loại nghỉ</th>
              <th>Ngày bắt đầu</th>
              <th>Ngày kết thúc</th>
              <th>Lý do</th>
              <th>Trạng thái</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="leave in leaveRequests" :key="leave.id">
              <td class="fw-semibold">
                {{ leave.employee_name }}
                <br>
                <small class="text-muted">{{ leave.employee_code }}</small>
              </td>
              <td>{{ leave.type }}</td>
              <td>{{ leave.start_date }}</td>
              <td>{{ leave.end_date }}</td>
              <td><span class="badge bg-light text-dark">{{ leave.days }} ngày</span></td>
              <td>
                <span :class="['badge', getStatusBadge(leave.status)]">
                  {{ leave.status }}
                </span>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <button v-if="leave.status === 'Chờ duyệt'" @click="approveLeave(leave)" class="btn btn-sm btn-success">
                    Duyệt
                  </button>
                  <button v-if="leave.status === 'Chờ duyệt'" @click="rejectLeave(leave)" class="btn btn-sm btn-warning">
                    Từ chối
                  </button>
                  <button @click="deleteLeave(leave)" class="btn btn-sm btn-danger">
                    Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { leaveRequestsService } from '../services/leaveRequestsService'

const leaveRequests = ref([])
const filters = ref({ status: '', search: '', page: 1, per_page: 10 })
const loading = ref(false)

onMounted(() => {
  fetchLeaveRequests()
})

const fetchLeaveRequests = async () => {
  loading.value = true
  try {
    const data = await leaveRequestsService.list({
      page: filters.value.page,
      per_page: filters.value.per_page,
      status: filters.value.status,
      search: filters.value.search
    })
    leaveRequests.value = data.data || []
  } catch (error) {
    console.error('Failed to load leave requests:', error)
  } finally {
    loading.value = false
  }
}

const approveLeave = async (leave) => {
  try {
    await leaveRequestsService.approve(leave.id)
    await fetchLeaveRequests()
  } catch (error) {
    console.error('Failed to approve:', error)
  }
}

const rejectLeave = async (leave) => {
  try {
    await leaveRequestsService.reject(leave.id)
    await fetchLeaveRequests()
  } catch (error) {
    console.error('Failed to reject:', error)
  }
}

const deleteLeave = async (leave) => {
  if (!confirm('Bạn chắc chắn muốn xóa đơn này?')) return
  try {
    await leaveRequestsService.delete(leave.id)
    await fetchLeaveRequests()
  } catch (error) {
    console.error('Failed to delete:', error)
  }
}

const getStatusBadge = (status) => {
  if (status === 'Chờ duyệt') return 'bg-warning'
  if (status === 'Đã duyệt') return 'bg-success'
  if (status === 'Từ chối') return 'bg-danger'
  return 'bg-secondary'
}
</script>
```

---

#### Backend - Routes

**File:** [backend/routes/api.php](backend/routes/api.php)

```php
Route::middleware('auth:sanctum')->group(function () {
    // Admin: Leave Requests management
    Route::middleware('role:admin')->prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/{id}/status', [LeaveRequestController::class, 'updateStatus']);
        Route::delete('/{id}', [LeaveRequestController::class, 'destroy']);
    });

    // Employee: My leaves
    Route::middleware('role:employee')->group(function () {
        Route::get('/my-leaves', [LeaveRequestController::class, 'myLeaves']);
        Route::post('/my-leaves', [LeaveRequestController::class, 'store']);
    });
});
```

---

#### Backend - LeaveRequestController - index()

**File:** [backend/app/Http/Controllers/Api/LeaveRequestController.php](backend/app/Http/Controllers/Api/LeaveRequestController.php)

```php
public function index(Request $request)
{
    // 1️⃣ Base query with eager loading
    $query = LeaveRequest::with('employee.user')
        ->orderBy('created_at', 'desc');

    // 2️⃣ Filter by status
    if ($status = $request->query('status')) {
        $query->where('status', $status);
    }

    // 3️⃣ Search by employee name/code
    if ($search = $request->query('search')) {
        $query->whereHas('employee.user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        })->orWhereHas('employee', function ($q) use ($search) {
            $q->where('employee_code', 'like', "%{$search}%");
        });
    }

    // 4️⃣ Pagination
    $perPage = (int) $request->query('per_page', 10);
    $leaves = $query->paginate($perPage);

    // 5️⃣ Transform and return
    return response()->json([
        'data' => $leaves->getCollection()->transform(fn ($leave) => $this->transformLeave($leave)),
        'meta' => [
            'current_page' => $leaves->currentPage(),
            'last_page' => $leaves->lastPage(),
            'per_page' => $leaves->perPage(),
            'total' => $leaves->total(),
        ],
    ]);
}

private function transformLeave(LeaveRequest $leave)
{
    $days = $leave->start_date->diffInDays($leave->end_date) + 1;
    
    return [
        'id' => $leave->id,
        'employee_id' => $leave->employee_id,
        'employee_name' => $leave->employee->user?->name,
        'employee_code' => $leave->employee->employee_code,
        'start_date' => $leave->start_date->format('d/m/Y'),
        'end_date' => $leave->end_date->format('d/m/Y'),
        'reason' => $leave->reason,
        'type' => $leave->type,
        'status' => $leave->status,
        'days' => $days,
        'created_at' => $leave->created_at->format('d/m/Y H:i'),
    ];
}
```

#### SQL Query

```sql
SELECT 
    lr.id, lr.employee_id, lr.start_date, lr.end_date, 
    lr.reason, lr.type, lr.status, lr.created_at,
    e.employee_code, u.name
FROM leave_requests lr
INNER JOIN employees e ON lr.employee_id = e.id
INNER JOIN users u ON e.user_id = u.id
WHERE 1 = 1
ORDER BY lr.created_at DESC
LIMIT 10 OFFSET 0
```

#### JSON Response

```json
{
  "data": [
    {
      "id": 5,
      "employee_id": 2,
      "employee_name": "Nguyễn Văn An",
      "employee_code": "EMP-00002",
      "start_date": "10/01/2026",
      "end_date": "12/01/2026",
      "reason": "Nghỉ phép năm",
      "type": "Vacation",
      "status": "Chờ duyệt",
      "days": 3,
      "created_at": "08/01/2026 10:30"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

---

### 2️⃣ Admin duyệt/từ chối đơn (POST /api/leave-requests/{id}/status)

#### Frontend - Approve/Reject Button Action

```vue
<script setup>
const approveLeave = async (leaveId) => {
  try {
    await leaveRequestsService.approve(leaveId)
    await fetchLeaveRequests()
  } catch (error) {
    console.error('Failed to approve:', error)
  }
}

const rejectLeave = async (leaveId) => {
  try {
    await leaveRequestsService.reject(leaveId)
    await fetchLeaveRequests()
  } catch (error) {
    console.error('Failed to reject:', error)
  }
}
</script>
```

#### Backend - updateStatus()

```php
public function updateStatus(Request $request, $id)
{
    // 1️⃣ Validate status
    $validated = $request->validate([
        'status' => ['required', Rule::in(['Đã duyệt', 'Từ chối'])],
    ]);

    // 2️⃣ Find leave request
    $leave = LeaveRequest::with('employee.user')->findOrFail($id);
    
    // 3️⃣ Update status
    $leave->update(['status' => $validated['status']]);

    // 4️⃣ Notify employee
    $this->notifyEmployee($leave);

    // 5️⃣ Return response
    return response()->json([
        'message' => 'Leave request updated successfully',
        'data' => $this->transformLeave($leave),
    ]);
}

private function notifyEmployee(LeaveRequest $leave)
{
    // Create notification in database
    $leave->employee->user->notifications()->create([
        'title' => 'Trạng thái đơn nghỉ: ' . $leave->status,
        'content' => 'Đơn nghỉ từ ' . $leave->start_date->format('d/m/Y') . ' được ' . 
                    ($leave->status === 'Đã duyệt' ? 'duyệt' : 'từ chối'),
        'is_read' => false,
    ]);

    // Try to send email (optional)
    try {
        Mail::to($leave->employee->user->email)->send(
            new LeaveRequestStatusMail($leave)
        );
    } catch (\Exception $e) {
        \Log::error('Failed to send email: ' . $e->getMessage());
    }
}
```

#### HTTP Request

```http
POST http://localhost:8000/api/leave-requests/5/status
Authorization: Bearer <admin-token>
Content-Type: application/json

{
  "status": "Đã duyệt"
}
```

#### SQL Query

```sql
UPDATE leave_requests SET status = 'Đã duyệt', updated_at = NOW() WHERE id = 5;
INSERT INTO notifications (user_id, title, content, is_read, created_at, updated_at)
VALUES (2, 'Trạng thái đơn nghỉ: Đã duyệt', 'Đơn nghỉ từ 10/01/2026 được duyệt', 0, NOW(), NOW());
```

---

### 3️⃣ Employee tạo đơn nghỉ (POST /api/my-leaves)

#### Frontend - Create Leave Form

**File:** [frontend/src/views/MyLeavesView.vue](frontend/src/views/MyLeavesView.vue)

```vue
<template>
  <AppLayout>
    <h2 class="mb-4">Quản lý đơn nghỉ phép của tôi</h2>

    <!-- Create Form -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="mb-4">Nộp đơn nghỉ mới</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Ngày bắt đầu *</label>
            <input v-model="form.start_date" type="date" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Ngày kết thúc *</label>
            <input v-model="form.end_date" type="date" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Loại nghỉ *</label>
            <select v-model="form.type" class="form-select">
              <option value="">Chọn loại</option>
              <option value="Vacation">Nghỉ phép hàng năm</option>
              <option value="Sick">Nghỉ ốm</option>
              <option value="Personal">Nghỉ cá nhân</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Lý do *</label>
            <textarea v-model="form.reason" class="form-control" rows="3" placeholder="Nhập lý do..."></textarea>
          </div>
          <div class="col-md-12">
            <button @click="submitLeave" class="btn btn-primary" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              Nộp đơn
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="mb-4">Lịch sử đơn nghỉ</h5>
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Loại</th>
                <th>Ngày bắt đầu</th>
                <th>Ngày kết thúc</th>
                <th>Lý do</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="leave in myLeaves" :key="leave.id">
                <td>{{ leave.type }}</td>
                <td>{{ leave.start_date }}</td>
                <td>{{ leave.end_date }}</td>
                <td>{{ leave.reason }}</td>
                <td>
                  <span :class="['badge', getStatusBadge(leave.status)]">
                    {{ leave.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { leaveRequestsService } from '../services/leaveRequestsService'

const myLeaves = ref([])
const submitting = ref(false)
const form = ref({
  start_date: '',
  end_date: '',
  type: '',
  reason: ''
})

onMounted(() => {
  fetchMyLeaves()
})

const fetchMyLeaves = async () => {
  try {
    const data = await leaveRequestsService.myList({ page: 1, per_page: 50 })
    myLeaves.value = data.data || []
  } catch (error) {
    console.error('Failed to load leaves:', error)
  }
}

const submitLeave = async () => {
  if (!form.value.start_date || !form.value.end_date || !form.value.type || !form.value.reason) {
    alert('Vui lòng điền tất cả trường')
    return
  }

  submitting.value = true
  try {
    await leaveRequestsService.create(form.value)
    form.value = { start_date: '', end_date: '', type: '', reason: '' }
    await fetchMyLeaves()
    alert('Nộp đơn thành công')
  } catch (error) {
    console.error('Failed to submit:', error)
    alert('Lỗi: ' + error.message)
  } finally {
    submitting.value = false
  }
}

const getStatusBadge = (status) => {
  if (status === 'Chờ duyệt') return 'badge bg-warning'
  if (status === 'Đã duyệt') return 'badge bg-success'
  if (status === 'Từ chối') return 'badge bg-danger'
  return 'badge bg-secondary'
}
</script>
```

---

#### Backend - store() [Employee create]

```php
public function store(Request $request)
{
    // 1️⃣ Get employee profile
    $employee = auth()->user()->employee;
    if (!$employee) {
        return response()->json(['message' => 'Employee profile not found'], 404);
    }

    // 2️⃣ Validate input
    $validated = $request->validate([
        'start_date' => ['required', 'date', 'date_format:Y-m-d'],
        'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        'reason' => ['required', 'string', 'max:500'],
        'type' => ['required', 'string', 'max:100'],
    ]);

    // 3️⃣ Create leave request (status = "Chờ duyệt" by default)
    $leave = LeaveRequest::create([
        'employee_id' => $employee->id,
        'start_date' => $validated['start_date'],
        'end_date' => $validated['end_date'],
        'reason' => $validated['reason'],
        'type' => $validated['type'],
        'status' => 'Chờ duyệt',
    ]);

    // 4️⃣ Notify all admins
    $this->notifyAdmins($leave);

    // 5️⃣ Return response
    return response()->json([
        'message' => 'Leave request submitted successfully',
        'data' => $this->transformLeave($leave->fresh('employee.user')),
    ], 201);
}

private function notifyAdmins(LeaveRequest $leave)
{
    // Get all admins
    $admins = User::where('role', 'admin')->get();

    foreach ($admins as $admin) {
        // Create DB notification
        $admin->notifications()->create([
            'title' => 'Đơn nghỉ mới từ ' . $leave->employee->user->name,
            'content' => 'Nhân viên ' . $leave->employee->user->name . ' vừa nộp đơn nghỉ từ ' . 
                        $leave->start_date->format('d/m/Y'),
            'is_read' => false,
        ]);

        // Try to send email
        try {
            Mail::to($admin->email)->send(
                new NewLeaveRequestMail($leave)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send email to admin: ' . $e->getMessage());
        }
    }
}
```

#### HTTP Request

```http
POST http://localhost:8000/api/my-leaves
Authorization: Bearer <employee-token>
Content-Type: application/json

{
  "start_date": "2026-01-10",
  "end_date": "2026-01-12",
  "reason": "Nghỉ phép năm",
  "type": "Vacation"
}
```

#### JSON Response

```json
{
  "message": "Leave request submitted successfully",
  "data": {
    "id": 5,
    "employee_id": 2,
    "employee_name": "Nguyễn Văn An",
    "employee_code": "EMP-00002",
    "start_date": "10/01/2026",
    "end_date": "12/01/2026",
    "reason": "Nghỉ phép năm",
    "type": "Vacation",
    "status": "Chờ duyệt",
    "days": 3,
    "created_at": "08/01/2026 10:30"
  }
}
```

---

### 4️⃣ Employee lấy danh sách đơn của mình (GET /api/my-leaves)

#### Backend - myLeaves()

```php
public function myLeaves(Request $request)
{
    // 1️⃣ Get employee profile
    $employee = auth()->user()->employee;
    if (!$employee) {
        return response()->json(['message' => 'Employee profile not found'], 404);
    }

    // 2️⃣ Query leaves
    $query = LeaveRequest::where('employee_id', $employee->id)
        ->with('employee.user')
        ->orderBy('created_at', 'desc');

    // 3️⃣ Pagination
    $perPage = (int) $request->query('per_page', 10);
    $leaves = $query->paginate($perPage);

    // 4️⃣ Transform and return
    return response()->json([
        'data' => $leaves->getCollection()->transform(fn ($leave) => $this->transformLeave($leave)),
        'meta' => [
            'current_page' => $leaves->currentPage(),
            'last_page' => $leaves->lastPage(),
            'per_page' => $leaves->perPage(),
            'total' => $leaves->total(),
        ],
    ]);
}
```

#### SQL Query

```sql
SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 10 OFFSET 0
```

---

## 📋 Tóm tắt API Endpoints

| Method | Endpoint | Controller | Vai trò | Mục đích |
|--------|----------|-----------|--------|----------|
| GET | `/api/leave-requests` | `index()` | Admin | Danh sách tất cả đơn (filter, paginate) |
| POST | `/api/leave-requests/{id}/status` | `updateStatus()` | Admin | Duyệt/từ chối đơn + notify employee |
| DELETE | `/api/leave-requests/{id}` | `destroy()` | Admin | Xóa đơn nghỉ |
| GET | `/api/my-leaves` | `myLeaves()` | Employee | Danh sách đơn của mình |
| POST | `/api/my-leaves` | `store()` | Employee | Tạo đơn mới + notify admins |

---

## 🗄️ Database Schema

```sql
CREATE TABLE leave_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    type VARCHAR(100),  -- 'Vacation', 'Sick', 'Personal'
    status VARCHAR(50) DEFAULT 'Chờ duyệt',  -- 'Chờ duyệt', 'Đã duyệt', 'Từ chối'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255),
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔐 Security & Authorization

### **Authentication Layer**
- Tất cả endpoint yêu cầu `auth:sanctum` middleware
- Bearer token phải được gửi trong header `Authorization: Bearer <token>`
- Token được sinh từ login flow (User → Sanctum token)

### **Authorization Layer**
- ✅ **Admin endpoints**: `/api/leave-requests/*` → `role:admin` middleware
  - Xem tất cả đơn
  - Duyệt/từ chối (update status)
  - Xóa đơn
  
- ✅ **Employee endpoints**: `/api/my-leaves` → `role:employee` middleware
  - Xem chỉ đơn của chính mình (filter theo `employee_id`)
  - Tạo đơn mới
  - Không thể sửa/xóa đơn của người khác

### **Data-level Security**
- Employee chỉ thấy `WHERE employee_id = auth()->user()->employee->id`
- Admin không được giới hạn, xem toàn bộ

### **Validation**
- `start_date` và `end_date`: kiểu `date` format `Y-m-d`
- `end_date >= start_date` (validated trong store/update)
- `reason`: bắt buộc, tối đa 500 ký tự
- `type`: bắt buộc, tối đa 100 ký tự
- `status`: chỉ cho phép "Đã duyệt" hoặc "Từ chối" khi admin cập nhật
- Employee profile kiểm tra: nếu user chưa có employee → trả 404

---

## ✅ Luồng hoàn chỉnh

```
┌─────────────────────────────────────────────────────────────┐
│ EMPLOYEE TẠO ĐƠN NGHỈ                                       │
└─────────────────────────────────────────────────────────────┘

1. Employee mở /my-leaves
   ↓
2. Điền form: start_date, end_date, type, reason
   ↓
3. Click "Nộp đơn"
   ↓
4. POST /api/my-leaves
   ↓
5. Middleware check:
   - auth:sanctum ✅
   - role:employee ✅
   ↓
6. Backend validation:
   - start_date >= today? ✓
   - end_date >= start_date? ✓
   - reason <= 500 chars? ✓
   - type <= 100 chars? ✓
   ↓
7. Database transaction:
   - INSERT leave_requests (status = 'Chờ duyệt')
   - INSERT notifications (for each admin)
   ↓
8. Email notifications (try-catch to avoid errors)
   - Send to all admins
   ↓
9. Return 201 Created with leave data
   ↓
10. Frontend: reset form, refresh list


┌─────────────────────────────────────────────────────────────┐
│ ADMIN DUYỆT/TỪ CHỐI ĐƠN                                    │
└─────────────────────────────────────────────────────────────┘

1. Admin vào /leave-requests
   ↓
2. Xem danh sách tất cả đơn (with filters)
   - Filter theo status (Chờ duyệt/Đã duyệt/Từ chối)
   - Search by employee name/code
   ↓
3. Click "Duyệt" hoặc "Từ chối"
   ↓
4. POST /api/leave-requests/{id}/status
   ↓
5. Middleware check:
   - auth:sanctum ✅
   - role:admin ✅
   ↓
6. Backend validation:
   - Status ∈ {'Đã duyệt', 'Từ chối'} ✓
   ↓
7. Database transaction:
   - UPDATE leave_requests SET status = ?
   - INSERT notification (to employee)
   ↓
8. Email notification to employee (try-catch)
   ↓
9. Return 200 OK
   ↓
10. Frontend refresh list → status thay đổi


┌─────────────────────────────────────────────────────────────┐
│ ADMIN XÓA ĐƠN                                               │
└─────────────────────────────────────────────────────────────┘

1. Admin click button "Xóa"
   ↓
2. DELETE /api/leave-requests/{id}
   ↓
3. Middleware check:
   - auth:sanctum ✅
   - role:admin ✅
   ↓
4. Database:
   - DELETE FROM leave_requests WHERE id = ?
   ↓
5. Return 200 OK
   ↓
6. Frontend refresh list → đơn biến mất
```

---

## 🎯 Key Features

### **1. Multi-role Support**
- **Admin**: quản lý toàn bộ đơn, duyệt/từ chối, xóa, search/filter
- **Employee**: tạo đơn riêng, xem lịch sử của mình, không thể sửa/xóa

### **2. Smart Filtering & Pagination**
- Admin filter by status, type, employee name/code
- Pagination: mặc định 10 per page, custom với `per_page` query param
- Sorting: created_at DESC (mới nhất trước)

### **3. Notification System**
- **Database notifications**: lưu vào bảng notifications (persistent)
- **Email notifications**: gửi via Mail (try-catch để không crash nếu mail chưa config)
- **Trigger**: khi employee tạo đơn (notify admins) + admin duyệt/từ chối (notify employee)

### **4. Validation & Business Rules**
- Date range: end_date >= start_date
- Text limits: reason (500 chars), type (100 chars)
- Status enum: chỉ "Chờ duyệt", "Đã duyệt", "Từ chối"
- Employee profile required: 404 nếu user chưa có hồ sơ employee

### **5. Automatic Calculations**
- Tính số ngày: `end_date - start_date + 1` (including both days)
- Display trên UI: badge với số ngày

### **6. Role-based Data Filtering**
- Employee chỉ thấy đơn của mình (via SQL WHERE employee_id = ?)
- Admin thấy tất cả
- Đảm bảo không access cross-employee data

---

## 🔍 Database Relationships

```
┌──────────────────────────────────────────────────┐
│                       users                      │
│  id (PK) | name | email | role | ...            │
│  1       | Admin User | admin@... | admin       │
│  2       | Nguyễn Văn An | an@... | employee    │
└──────────────────┬───────────────────────────────┘
                   │
                   ├─────────────────────┐
                   │                     │
                   ▼ 1                   ▼ 1
         ┌─────────────────────┐  ┌──────────────────┐
         │   employees         │  │  notifications   │
         │  user_id (FK) ◄─────┤  │  user_id (FK)    │
         └────────┬────────────┘  └──────────────────┘
                  │ 1
                  │
                  ▼ N
        ┌──────────────────────────┐
        │   leave_requests         │
        │  employee_id (FK) ◄──────┤
        │  status, type            │
        │  start_date, end_date    │
        └──────────────────────────┘

Flow:
1. User tạo employee record
2. Employee tạo leave_request
3. Admin duyệt → INSERT notification for employee user
4. Employee thấy notification ở bell icon
```

Toàn bộ quá trình đảm bảo **role-based access control**, **data consistency**, **notification delivery**, **efficient filtering/pagination**, và **error handling**! 🎯
