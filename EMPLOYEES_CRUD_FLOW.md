# 👥 Luồng hoạt động EMPLOYEES CRUD (Admin Only) - Frontend → Backend → Database

## 📈 Sơ đồ tổng quát

```
┌───────────────────────────────────────────────────────────────┐
│                    ADMIN USER                                 │
│  Mở trang /employees với role: admin                          │
│  Thực hiện: Xem / Tạo / Sửa / Xóa nhân viên                  │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. EmployeesView.vue                                          │
│    - onMounted() → fetchEmployees()                           │
│    - Modal form for create/edit                               │
│    - Action buttons (view/edit/delete)                        │
│                          ↓                                    │
│ 2. Employees Service (employeesService)                       │
│    - getEmployees(params) → GET /api/employees?page=...       │
│    - getEmployee(id) → GET /api/employees/{id}                │
│    - createEmployee(data) → POST /api/employees               │
│    - updateEmployee(id, data) → PUT /api/employees/{id}       │
│    - deleteEmployee(id) → DELETE /api/employees/{id}          │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Requests (Bearer token)
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 3. Routes (api.php) - auth:sanctum + role:admin               │
│    - GET    /api/employees                  [List]            │
│    - POST   /api/employees                  [Create]          │
│    - GET    /api/employees/{id}             [Show]            │
│    - PUT    /api/employees/{id}             [Update]          │
│    - DELETE /api/employees/{id}             [Delete]          │
│                          ↓                                    │
│ 4. EmployeeController (CRUD operations)                       │
│    - index(): list with filters & pagination                  │
│    - store(): create employee + user together                 │
│    - show(): get single employee                              │
│    - update(): update employee + user                         │
│    - destroy(): delete employee + user                        │
│                          ↓                                    │
│ 5. Models (Eloquent ORM)                                      │
│    - Employee (user_id FK, position, department, etc)         │
│    - User (role, email, password - hashed)                    │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 6. Two-table operations:                                      │
│    - users table (INSERT/UPDATE/DELETE)                       │
│    - employees table (INSERT/UPDATE/DELETE)                   │
│    - Foreign key: employees.user_id → users.id                │
│                          ↓                                    │
│ 7. Queries:                                                   │
│    - INSERT users → INSERT employees (linked)                 │
│    - UPDATE users + employees (same employee)                 │
│    - DELETE users (CASCADE → employees)                       │
└───────────────────────────────────────────────────────────────┘
                          ↓
            ✅ Employee list updated in UI
       (with filters, pagination, search)
```

---

## 🔄 Chi tiết từng luồng

### 1️⃣ **Lấy danh sách nhân viên (GET /api/employees)**

#### **Frontend - Employees List Component**

**File:** `frontend/src/views/EmployeesView.vue`

```vue
<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Quản lý nhân viên</h2>
      <button class="btn btn-primary" @click="openCreate">
        <i class="bi bi-plus"></i> Thêm nhân viên
      </button>
    </div>

    <!-- Search Bar -->
    <div class="mb-4">
      <input 
        v-model="filters.search" 
        type="text" 
        class="form-control form-control-lg" 
        placeholder="Tìm kiếm theo tên, email, vị trí hoặc phòng ban..."
        @keyup.enter="fetchEmployees()"
      />
    </div>

    <!-- Employees Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Họ và tên</th>
              <th>Vị trí</th>
              <th>Phòng ban</th>
              <th>Email</th>
              <th>Trạng thái</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
            <!-- 👇 Loop through employees -->
            <tr v-for="emp in employees" :key="emp.id">
              <td class="fw-500">{{ emp.name }}</td>
              <td>{{ emp.position }}</td>
              <td>{{ emp.department }}</td>
              <td>{{ emp.email }}</td>
              <td>
                <span :class="emp.status === 'Đang làm việc' ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ emp.status }}
                </span>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <button @click="viewEmployee(emp)" class="btn btn-sm btn-link text-primary">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button @click="openEdit(emp)" class="btn btn-sm btn-link text-primary">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button @click="confirmDelete(emp)" class="btn btn-sm btn-link text-danger">
                    <i class="bi bi-trash"></i>
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
import employeesService from '../services/employeesService'

const employees = ref([])
const filters = ref({ search: '', status: '', per_page: 10 })
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)

onMounted(() => {
  fetchEmployees()
})

const fetchEmployees = async () => {
  loading.value = true
  try {
    // 👇 Call service with filters
    const data = await employeesService.getEmployees({
      page: filters.value.current_page || 1,
      per_page: filters.value.per_page,
      search: filters.value.search,
      status: filters.value.status
    })
    
    employees.value = data.data || []
    if (data.meta) {
      pagination.value = data.meta
    }
  } catch (err) {
    console.error('Failed to load employees:', err)
  } finally {
    loading.value = false
  }
}
</script>
```

**User opens /employees** → Trigger `onMounted()` → `fetchEmployees()` ↓

---

#### **Frontend - Employees Service**

**File:** `frontend/src/services/employeesService.js`

```javascript
import apiClient from './api'

const employeesService = {
  async getEmployees(params = {}) {
    const { page = 1, per_page = 10, status = '', search = '' } = params
    try {
      // 👇 HTTP GET request with query params
      const response = await apiClient.get('/api/employees', {
        params: { page, per_page, status, search }
      })
      return response.data  // { data: [...], meta: { ... } }
    } catch (error) {
      throw error
    }
  },

  async getEmployee(id) {
    try {
      // 👇 Get single employee
      const response = await apiClient.get(`/api/employees/${id}`)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async createEmployee(payload) {
    try {
      // 👇 POST new employee
      const response = await apiClient.post('/api/employees', payload)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async updateEmployee(id, payload) {
    try {
      // 👇 PUT update employee
      const response = await apiClient.put(`/api/employees/${id}`, payload)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async deleteEmployee(id) {
    try {
      // 👇 DELETE employee
      const response = await apiClient.delete(`/api/employees/${id}`)
      return response.data
    } catch (error) {
      throw error
    }
  }
}

export default employeesService
```

**HTTP Request:**

```http
GET http://localhost:8000/api/employees?page=1&per_page=10&search=John&status=
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
    // Employees CRUD - ADMIN ONLY
    Route::middleware('role:admin')->prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);      // List
        Route::post('/', [EmployeeController::class, 'store']);     // Create
        Route::get('/{id}', [EmployeeController::class, 'show']);   // Show
        Route::put('/{id}', [EmployeeController::class, 'update']); // Update
        Route::delete('/{id}', [EmployeeController::class, 'destroy']); // Delete
    });
});
```

**Middleware checks:**
- ✅ User authenticated? (via `auth:sanctum`)
- ✅ User is admin? (via `role:admin`)

↓

---

#### **Backend - EmployeeController**

**File:** `backend/app/Http/Controllers/Api/EmployeeController.php`

```php
public function index(Request $request)
{
    // 1️⃣ Base query with user relationship
    $query = Employee::with('user')->orderByDesc('created_at');

    // 2️⃣ Filter by status if provided
    if ($status = $request->query('status')) {
        $query->where('status', $status);
    }

    // 3️⃣ Search filter (search in multiple fields)
    if ($search = $request->query('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('employee_code', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%")
                ->orWhere('position', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    // 4️⃣ Pagination
    $perPage = (int) $request->query('per_page', 10);
    $employees = $query->paginate($perPage);

    // 5️⃣ Transform and return response
    return response()->json([
        'data' => $employees->getCollection()->transform(fn ($emp) => $this->transformEmployee($emp)),
        'meta' => [
            'current_page' => $employees->currentPage(),
            'last_page' => $employees->lastPage(),
            'per_page' => $employees->perPage(),
            'total' => $employees->total(),
        ],
    ]);
}

private function transformEmployee(Employee $employee)
{
    return [
        'id' => $employee->id,
        'user_id' => $employee->user_id,
        'name' => $employee->user?->name,
        'email' => $employee->user?->email,
        'role' => $employee->user?->role,
        'employee_code' => $employee->employee_code,
        'position' => $employee->position,
        'department' => $employee->department,
        'phone' => $employee->phone,
        'status' => $employee->status,
        'created_at' => optional($employee->created_at)->format('d/m/Y H:i'),
    ];
}
```

↓

---

#### **Backend - Database Query**

**Eloquent ORM:**

```php
Employee::with('user')
    ->where('status', 'Đang làm việc')
    ->where('department', 'like', '%IT%')
    ->orderByDesc('created_at')
    ->paginate(10)
```

**Converts to SQL:**

```sql
SELECT 
    e.id, e.user_id, e.employee_code, e.position, e.department, 
    e.phone, e.status, e.created_at,
    u.id, u.name, u.email, u.role
FROM employees e
INNER JOIN users u ON e.user_id = u.id
WHERE e.status = 'Đang làm việc'
ORDER BY e.created_at DESC
LIMIT 10 OFFSET 0
```

**Database Structure:**

```
┌────────────────────────────────────────────────┐
│ users table                                    │
├────────────────────────────────────────────────┤
│ id: 1                                          │
│ name: "John Doe"                               │
│ email: "john@company.com"                      │
│ password: hashed_password_bcrypt               │
│ role: "admin" or "employee"                    │
│ created_at, updated_at                         │
└────────────────────────────────────────────────┘

┌────────────────────────────────────────────────┐
│ employees table                                │
├────────────────────────────────────────────────┤
│ id: 1                                          │
│ user_id: 1 (FK → users)                        │
│ employee_code: "EMP-00001"                     │
│ position: "Senior Developer"                   │
│ department: "IT"                               │
│ phone: "0123456789"                            │
│ status: "Đang làm việc"                        │
│ created_at, updated_at                         │
└────────────────────────────────────────────────┘
```

↓

---

#### **Backend - JSON Response**

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "John Doe",
      "email": "john@company.com",
      "role": "employee",
      "employee_code": "EMP-00001",
      "position": "Senior Developer",
      "department": "IT",
      "phone": "0123456789",
      "status": "Đang làm việc",
      "created_at": "05/01/2026 10:30"
    },
    {
      "id": 2,
      "user_id": 2,
      "name": "Jane Smith",
      "email": "jane@company.com",
      "role": "employee",
      "employee_code": "EMP-00002",
      "position": "HR Manager",
      "department": "HR",
      "phone": "0123456790",
      "status": "Đang làm việc",
      "created_at": "04/01/2026 14:20"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 2
  }
}
```

↓

---

#### **Frontend - Display List**

```javascript
// Receive response
const data = await employeesService.getEmployees({ page: 1, per_page: 10 })

// Update state
employees.value = data.data || []
pagination.value = data.meta

// Vue renders table with 10 rows per page
```

---

### 2️⃣ **Tạo nhân viên mới (POST /api/employees)**

#### **Frontend - Create Modal Form**

```vue
<template>
  <!-- Create/Edit Modal -->
  <div v-if="showModal" class="modal-backdrop d-flex">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ editingId ? 'Chỉnh sửa nhân viên' : 'Thêm nhân viên' }}</h5>
          <button @click="closeModal" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ và tên *</label>
              <input v-model="form.name" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input v-model="form.email" type="email" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Vị trí *</label>
              <input v-model="form.position" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Phòng ban *</label>
              <input v-model="form.department" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Mật khẩu *</label>
              <input v-model="form.password" type="password" class="form-control" placeholder="Tối thiểu 6 ký tự" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="Đang làm việc">Đang làm việc</option>
                <option value="Nghỉ việc">Nghỉ việc</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn btn-secondary">Đóng</button>
          <button @click="saveEmployee" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Đang lưu...' : 'Lưu' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const form = ref({
  name: '',
  email: '',
  position: '',
  department: '',
  password: '',
  status: 'Đang làm việc'
})

const openCreate = () => {
  editingId.value = null
  form.value = { name: '', email: '', position: '', department: '', password: '', status: 'Đang làm việc' }
  showModal.value = true
}

const saveEmployee = async () => {
  // Validation
  if (!form.value.name || !form.value.email || !form.value.position || !form.value.department) {
    formError.value = 'Please fill in all required fields'
    return
  }

  saving.value = true
  try {
    if (editingId.value) {
      // Update existing
      await employeesService.updateEmployee(editingId.value, form.value)
    } else {
      // Create new
      await employeesService.createEmployee(form.value)
    }
    await fetchEmployees()
    closeModal()
  } catch (err) {
    formError.value = err.message
  } finally {
    saving.value = false
  }
}
</script>
```

**Admin clicks "Thêm nhân viên"** → Modal opens → Fills form → Clicks "Lưu" ↓

---

#### **Frontend - Service Call**

```javascript
const createEmployee = async (payload) => {
  try {
    // 👇 POST request with form data
    const response = await apiClient.post('/api/employees', payload)
    return response.data
  } catch (error) {
    throw error
  }
}
```

**HTTP Request:**

```http
POST http://localhost:8000/api/employees
Headers:
  Authorization: Bearer 1|adminToken123...
  Content-Type: application/json

Body:
{
  "name": "Alice Johnson",
  "email": "alice@company.com",
  "password": "securePass123",
  "position": "Product Manager",
  "department": "Product",
  "phone": "0987654321",
  "status": "Đang làm việc"
}
```

↓

---

#### **Backend - EmployeeController - Create**

```php
public function store(Request $request)
{
    // 1️⃣ Validate input
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['nullable', 'string', 'min:6'],
        'role' => ['nullable', Rule::in(['admin', 'employee'])],
        'employee_code' => ['nullable', 'string', 'max:50', 'unique:employees,employee_code'],
        'position' => ['required', 'string', 'max:255'],
        'department' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'status' => ['nullable', Rule::in(['Đang làm việc', 'Nghỉ việc'])],
    ]);

    // 2️⃣ Set defaults
    $password = $validated['password'] ?? 'password123';
    $role = $validated['role'] ?? 'employee';
    $employeeCode = $validated['employee_code'] ?? $this->generateEmployeeCode();

    // 3️⃣ Create user record
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($password),  // 👈 Hash password with bcrypt
        'role' => $role,
    ]);

    // 4️⃣ Create employee record linked to user
    $employee = Employee::create([
        'user_id' => $user->id,              // 👈 Link to created user
        'employee_code' => $employeeCode,
        'position' => $validated['position'],
        'department' => $validated['department'],
        'phone' => $validated['phone'] ?? null,
        'status' => $validated['status'] ?? 'Đang làm việc',
    ]);

    // 5️⃣ Return created employee
    return response()->json([
        'message' => 'Employee created successfully',
        'data' => $this->transformEmployee($employee->load('user')),
    ], 201);  // 201 = Created
}

private function generateEmployeeCode(): string
{
    $nextNumber = (Employee::max('id') ?? 0) + 1;
    return 'EMP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
}
```

↓

---

#### **Backend - Database Transactions**

**Transaction flow:**

```
BEGIN TRANSACTION
  ↓
INSERT INTO users (name, email, password, role)
VALUES ('Alice Johnson', 'alice@company.com', '$2y$10$hashed...', 'employee')
  ↓ Returns user.id = 3
  ↓
INSERT INTO employees (user_id, employee_code, position, department, phone, status)
VALUES (3, 'EMP-00003', 'Product Manager', 'Product', '0987654321', 'Đang làm việc')
  ↓ Returns employee.id = 3
  ↓
COMMIT TRANSACTION
```

**SQL:**

```sql
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES ('Alice Johnson', 'alice@company.com', '$2y$10$hashed_password', 'employee', NOW(), NOW())

-- Returns last_insert_id = 3

INSERT INTO employees (user_id, employee_code, position, department, phone, status, created_at, updated_at)
VALUES (3, 'EMP-00003', 'Product Manager', 'Product', '0987654321', 'Đang làm việc', NOW(), NOW())

-- Returns last_insert_id = 3
```

**Response:**

```json
{
  "message": "Employee created successfully",
  "data": {
    "id": 3,
    "user_id": 3,
    "name": "Alice Johnson",
    "email": "alice@company.com",
    "role": "employee",
    "employee_code": "EMP-00003",
    "position": "Product Manager",
    "department": "Product",
    "phone": "0987654321",
    "status": "Đang làm việc",
    "created_at": "07/01/2026 15:45"
  }
}
```

---

### 3️⃣ **Cập nhật nhân viên (PUT /api/employees/{id})**

#### **Frontend - Edit Form**

```vue
<script setup>
const openEdit = (emp) => {
  editingId.value = emp.id
  form.value = {
    name: emp.name,
    email: emp.email,
    position: emp.position,
    department: emp.department,
    password: '',  // Leave empty - only update if provided
    status: emp.status
  }
  showModal.value = true
}
</script>
```

**Admin clicks edit button** → Form pre-filled with current data → Edits fields → Clicks "Lưu" ↓

---

#### **HTTP Request**

```http
PUT http://localhost:8000/api/employees/3
Headers:
  Authorization: Bearer 1|adminToken123...
  Content-Type: application/json

Body:
{
  "name": "Alice Johnson Updated",
  "email": "alice.new@company.com",
  "password": "",  -- Optional: leave empty to not change
  "position": "Senior Product Manager",
  "department": "Product",
  "phone": "0987654321",
  "status": "Đang làm việc"
}
```

---

#### **Backend - EmployeeController - Update**

```php
public function update(Request $request, $id)
{
    // 1️⃣ Find employee with user
    $employee = Employee::with('user')->findOrFail($id);

    // 2️⃣ Validate input
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
        'password' => ['nullable', 'string', 'min:6'],
        'role' => ['nullable', Rule::in(['admin', 'employee'])],
        'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
        'position' => ['required', 'string', 'max:255'],
        'department' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'status' => ['required', Rule::in(['Đang làm việc', 'Nghỉ việc'])],
    ]);

    // 3️⃣ Update user record
    $employee->user->update([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'role' => $validated['role'] ?? $employee->user->role,
        // Only hash password if provided
        'password' => $validated['password'] 
            ? Hash::make($validated['password']) 
            : $employee->user->password,
    ]);

    // 4️⃣ Update employee record
    $employee->update([
        'employee_code' => $validated['employee_code'],
        'position' => $validated['position'],
        'department' => $validated['department'],
        'phone' => $validated['phone'] ?? null,
        'status' => $validated['status'],
    ]);

    // 5️⃣ Return updated employee
    return response()->json([
        'message' => 'Employee updated successfully',
        'data' => $this->transformEmployee($employee->fresh('user')),
    ]);
}
```

**Key Points:**
- ✅ Unique validation ignores current user/employee
- ✅ Password only hashed if provided (not empty)
- ✅ Both `users` and `employees` tables updated

---

#### **SQL Update**

```sql
UPDATE users 
SET name = 'Alice Johnson Updated',
    email = 'alice.new@company.com',
    updated_at = NOW()
WHERE id = 3

UPDATE employees
SET employee_code = 'EMP-00003',
    position = 'Senior Product Manager',
    department = 'Product',
    status = 'Đang làm việc',
    updated_at = NOW()
WHERE id = 3
```

---

### 4️⃣ **Xóa nhân viên (DELETE /api/employees/{id})**

#### **Frontend - Delete Confirmation**

```vue
<script setup>
const confirmDelete = (emp) => {
  if (confirm(`Are you sure you want to delete ${emp.name}?`)) {
    deleteEmployee(emp.id)
  }
}

const deleteEmployee = async (id) => {
  try {
    await employeesService.deleteEmployee(id)
    await fetchEmployees()
  } catch (err) {
    console.error('Failed to delete:', err)
  }
}
</script>
```

**Admin clicks delete button** → Confirmation dialog → Confirms → Employee deleted ↓

---

#### **HTTP Request**

```http
DELETE http://localhost:8000/api/employees/3
Headers:
  Authorization: Bearer 1|adminToken123...
```

---

#### **Backend - EmployeeController - Delete**

```php
public function destroy($id)
{
    // 1️⃣ Find employee with user
    $employee = Employee::with('user')->findOrFail($id);

    // 2️⃣ Prevent self-deletion
    if (auth()->id() === $employee->user_id) {
        return response()->json(
            ['message' => 'Cannot delete your own account'], 
            403
        );
    }

    // 3️⃣ Delete user (CASCADE deletes employee due to FK)
    $employee->user->delete();

    // 4️⃣ Return success response
    return response()->json([
        'message' => 'Employee deleted successfully',
    ]);
}
```

**Safety Checks:**
- ✅ Prevents admin from deleting their own account
- ✅ Uses foreign key CASCADE to delete employee record

---

#### **SQL Delete with CASCADE**

```sql
-- When deleting from users table, employee record is auto-deleted
-- due to FOREIGN KEY constraint with ON DELETE CASCADE

DELETE FROM users WHERE id = 3
-- Automatically triggers:
-- DELETE FROM employees WHERE user_id = 3
```

**Database foreign key:**

```sql
ALTER TABLE employees ADD CONSTRAINT fk_user_id
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

---

### 5️⃣ **Lấy chi tiết nhân viên (GET /api/employees/{id})**

#### **Frontend**

```javascript
const viewEmployee = async (emp) => {
  try {
    const data = await employeesService.getEmployee(emp.id)
    console.log('Employee details:', data)
    // Can show in modal or detail page
  } catch (error) {
    console.error('Failed to load:', error)
  }
}
```

#### **Backend**

```php
public function show($id)
{
    // 👇 Find employee with user relationship
    $employee = Employee::with('user')->findOrFail($id);
    
    return response()->json([
        'data' => $this->transformEmployee($employee),
    ]);
}
```

#### **Response**

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "John Doe",
    "email": "john@company.com",
    "role": "employee",
    "employee_code": "EMP-00001",
    "position": "Senior Developer",
    "department": "IT",
    "phone": "0123456789",
    "status": "Đang làm việc",
    "created_at": "05/01/2026 10:30"
  }
}
```

---

## 📋 Tóm tắt API Endpoints

| Method | Endpoint | Controller | Action | Mục đích |
|--------|----------|-----------|--------|----------|
| GET | `/api/employees` | `index()` | READ | Danh sách (paginated, searchable) |
| POST | `/api/employees` | `store()` | CREATE | Tạo nhân viên mới |
| GET | `/api/employees/{id}` | `show()` | READ | Lấy chi tiết nhân viên |
| PUT | `/api/employees/{id}` | `update()` | UPDATE | Cập nhật nhân viên |
| DELETE | `/api/employees/{id}` | `destroy()` | DELETE | Xóa nhân viên |

---

## 🗄️ Database Schema

**Two linked tables:**

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- hashed with bcrypt
    role ENUM('admin', 'employee') DEFAULT 'employee',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

CREATE TABLE employees (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,  -- FK → users
    employee_code VARCHAR(20) NOT NULL UNIQUE,  -- auto: EMP-00001
    position VARCHAR(255),
    department VARCHAR(255),
    phone VARCHAR(20),
    status VARCHAR(50),  -- 'Đang làm việc', 'Nghỉ việc'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_employee_code (employee_code),
    INDEX idx_status (status),
    INDEX idx_department (department)
);
```

**Relationships:**
- 1 User : 1 Employee (for employees only - admins have no employee record)
- Deleting a User cascades to Employee (if exists)

---

## 🔐 Security & Authorization

### **Authentication Layer**
- All endpoints require `auth:sanctum` middleware
- Bearer token must be sent in Authorization header

### **Authorization Layer - Admin Only**
- ✅ All CRUD endpoints protected by `role:admin` middleware
- ❌ Only users with `role = 'admin'` can access
- ❌ Employees trying to access → 403 Forbidden

**Middleware Check:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('employees')->group(function () {
        // All CRUD operations - admin only
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    });
});
```

### **Validation & Business Rules**
1. **Email uniqueness** (per user)
2. **Employee code uniqueness** (auto-generated if not provided)
3. **Password minimum 6 chars** (hashed with bcrypt before storage)
4. **Status** must be "Đang làm việc" or "Nghỉ việc"
5. **Cannot delete own account** (403 Forbidden)

---

## ✅ Tóm tắt luồng hoàn chỉnh

```
┌─────────────────────────────────────────────────────────────┐
│ ADMIN OPENS EMPLOYEES PAGE (/employees)                     │
└─────────────────────────────────────────────────────────────┘

1. Component mounted
   ↓
2. Fetch employees list
   ↓
3. GET /api/employees?page=1&per_page=10&search=...
   ↓
4. Middleware: auth:sanctum ✅ + role:admin ✅
   ↓
5. SELECT employees JOIN users (with filters & pagination)
   ↓
6. Display table with 5 action columns:
   ├─ Employee name (from users.name)
   ├─ Position (from employees.position)
   ├─ Department (from employees.department)
   ├─ Email (from users.email)
   ├─ Status (from employees.status)
   └─ Actions: View / Edit / Delete


┌─────────────────────────────────────────────────────────────┐
│ CREATE NEW EMPLOYEE                                         │
└─────────────────────────────────────────────────────────────┘

1. Admin clicks "Thêm nhân viên"
   ↓
2. Modal form opens
   ↓
3. Admin fills: name, email, position, department, password
   ↓
4. Admin clicks "Lưu"
   ↓
5. POST /api/employees with form data
   ↓
6. Middleware check: auth:sanctum ✅ + role:admin ✅
   ↓
7. Validation: unique email, required fields, password length
   ✓ Passed
   ↓
8. Transaction:
   - INSERT INTO users (name, email, password_hash, role)
   - INSERT INTO employees (user_id, position, department, ...)
   ↓
9. Return 201 Created with new employee data
   ↓
10. Frontend shows success message
    ↓
11. Refresh employee list → New employee appears


┌─────────────────────────────────────────────────────────────┐
│ UPDATE EMPLOYEE                                             │
└─────────────────────────────────────────────────────────────┘

1. Admin clicks edit button for an employee
   ↓
2. Modal pre-fills with current data
   ↓
3. Admin modifies fields
   ↓
4. Clicks "Lưu"
   ↓
5. PUT /api/employees/{id} with updated data
   ↓
6. Validation: unique email (ignore current), unique code (ignore current)
   ✓ Passed
   ↓
7. Update users table AND employees table
   ↓
8. Return 200 OK with updated employee
   ↓
9. Frontend refreshes list


┌─────────────────────────────────────────────────────────────┐
│ DELETE EMPLOYEE                                             │
└─────────────────────────────────────────────────────────────┘

1. Admin clicks delete button
   ↓
2. Confirmation dialog: "Sure you want to delete [name]?"
   ↓
3. Admin confirms
   ↓
4. DELETE /api/employees/{id}
   ↓
5. Middleware: auth:sanctum ✅ + role:admin ✅
   ↓
6. Find employee & user
   ↓
7. Check: Admin != employee.user_id ✓
   ↓
8. DELETE FROM users WHERE id = employee.user_id
   (CASCADE → DELETE FROM employees)
   ↓
9. Return 200 OK success message
   ↓
10. Frontend removes from list
```

---

## 🎯 Key Features

### **1. Search & Filter**
- Search by: name, email, position, department, employee_code
- Filter by status (Active/Inactive)
- **Implemented via `whereHas()` for user relationship**

### **2. Pagination**
- Default 10 per page, configurable
- Shows current_page, last_page, total count, per_page
- **Uses Laravel's built-in `paginate()`**

### **3. Two-Model Create**
- Creating an employee requires creating a user first
- User gets employee_id relationship
- Both records linked via foreign key

### **4. Smart Validation**
- Email must be unique across all users
- Employee code must be unique or auto-generated
- Password hashing with bcrypt (one-way)
- Prevents self-deletion

### **5. Data Transformation**
- Backend returns combined data from both tables
- `transformEmployee()` merges user + employee fields
- Frontend receives single employee object

---

## 🔍 Database Relationships

```
┌──────────────────────────────────────────────────────────┐
│                       users                              │
│  id (PK) | name | email | password | role | ...         │
│  1       | John Doe | john@... | $2y$10$... | employee  │
│  2       | Jane Smith | jane@... | $2y$10$... | employee │
│  3       | Alice Johnson | alice@... | $2y$10$... | admin│
└──────────────────┬───────────────────────────────────────┘
                   │ 1 (for employees)
                   │
                   ▼ N
         ┌─────────────────────────────┐
         │    employees                │
         │  id | user_id | position    │
         │  1  | 1 (FK) | Developer   │
         │  2  | 2 (FK) | Manager     │
         │  (admin users don't have employee record)
         └─────────────────────────────┘

Admin operations:
├─ List all employees (JOIN users)
├─ Create employee + user together
├─ Update both records
└─ Delete user (CASCADE → employee)
```

Toàn bộ quá trình đảm bảo **role-based access control**, **input validation**, **data consistency**, **password security (bcrypt)**, và **efficient filtering/pagination**! 🎯
