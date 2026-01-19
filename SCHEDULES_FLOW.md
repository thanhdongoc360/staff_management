# 📅 Luồng hoạt động SCHEDULES - Frontend → Backend → Database

## 📊 Sơ đồ tổng quát

```
┌───────────────────────────────────────────────────────────────┐
│                    USER ACTION                                │
│  1. Admin: Xem/Thêm/Sửa/Xóa lịch làm việc                    │
│  2. Employee: Xem lịch làm việc trên Dashboard                │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. DashboardView.vue hoặc Admin page                          │
│    - onMounted() → call scheduleService methods               │
│                          ↓                                    │
│ 2. Schedule Service (dashboardService)                        │
│    - getSchedules(page) → GET /api/schedules?page=N           │
│    - getTodaySchedules() → GET /api/schedules/today           │
│    - getUpcomingSchedules() → GET /api/schedules/upcoming     │
│    - getScheduleById(id) → GET /api/schedules/{id}            │
│    - createSchedule(data) → POST /api/schedules (admin)       │
│    - updateSchedule(id, data) → PUT /api/schedules/{id} (admin) │
│    - deleteSchedule(id) → DELETE /api/schedules/{id} (admin)  │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Request (Bearer token)
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 3. Routes (api.php) - auth:sanctum middleware                 │
│    - GET  /api/schedules                    [View all]        │
│    - GET  /api/schedules/today              [Today's]         │
│    - GET  /api/schedules/upcoming           [Next 7 days]     │
│    - GET  /api/schedules/{id}               [Single]          │
│    - POST /api/schedules                    [Create - admin]  │
│    - PUT  /api/schedules/{id}               [Update - admin]  │
│    - DELETE /api/schedules/{id}             [Delete - admin]  │
│                          ↓                                    │
│ 4. ScheduleController                                         │
│    - index(): paginate all schedules                          │
│    - today(): get today's schedules                           │
│    - upcoming(): get next 7 days schedules                    │
│    - show($id): get single schedule                           │
│    - store(Request): create new schedule                      │
│    - update($id, Request): update schedule                    │
│    - destroy($id): delete schedule                            │
│                          ↓                                    │
│ 5. Schedule Model (Eloquent ORM)                              │
│    - Fillable: title, date, time, description                │
│    - Casts: date (date), time (datetime:H:i)                  │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 6. schedules table                                            │
│    - id, title, date, time, description                       │
│    - created_at, updated_at                                   │
│                          ↓                                    │
│ 7. Query examples:                                            │
│    SELECT * FROM schedules                                    │
│      ORDER BY date ASC, time ASC LIMIT 10                     │
│    SELECT * FROM schedules WHERE date = CURDATE()             │
│    INSERT INTO schedules VALUES (...)                         │
│    UPDATE schedules SET ... WHERE id = X                      │
│    DELETE FROM schedules WHERE id = X                         │
└───────────────────────────────────────────────────────────────┘
                          ↓
            ✅ Frontend displays schedules
```

---

## 🔄 Chi tiết từng luồng

### 1️⃣ **Lấy danh sách lịch làm việc (GET /api/schedules)**

#### **Frontend - Employee Dashboard**

**File:** `frontend/src/views/DashboardView.vue`

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { scheduleService } from '../services/dashboardService'

const workSchedule = ref([])
const loading = ref(false)

onMounted(() => {
  loadSchedules()
})

const loadSchedules = async () => {
  loading.value = true
  try {
    // 👇 Call schedule service
    const res = await scheduleService.getSchedules(1)
    workSchedule.value = res.data || []
  } catch (error) {
    console.error('Failed to load schedules:', error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="schedule-list">
    <div v-for="schedule in workSchedule" :key="schedule.id" class="schedule-item mb-3 pb-3 border-bottom">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">{{ schedule.title }}</div>
          <div class="text-muted small">
            📅 {{ schedule.date }} ⏰ {{ schedule.time }}
          </div>
          <div class="text-muted small" v-if="schedule.description">
            {{ schedule.description }}
          </div>
        </div>
        <span class="badge bg-primary-subtle text-primary">{{ schedule.date }}</span>
      </div>
    </div>
  </div>
</template>
```

**User opens Dashboard** → Trigger `onMounted()` → `loadSchedules()` ↓

---

#### **Frontend - Schedule Service**

**File:** `frontend/src/services/dashboardService.js`

```javascript
import apiClient from './api'

export const scheduleService = {
  async getSchedules(page = 1) {
    try {
      // 👇 HTTP GET request - List all schedules
      const response = await apiClient.get(`/api/schedules?page=${page}`)
      return response.data  // { data: [...], total: N, per_page: 10, ... }
    } catch (error) {
      throw error
    }
  },

  async getTodaySchedules() {
    try {
      // 👇 Get today's schedules only
      const response = await apiClient.get('/api/schedules/today')
      return response.data  // { data: [...], count: N }
    } catch (error) {
      throw error
    }
  },

  async getUpcomingSchedules() {
    try {
      // 👇 Get next 7 days schedules
      const response = await apiClient.get('/api/schedules/upcoming')
      return response.data  // { data: [...], count: N }
    } catch (error) {
      throw error
    }
  }
}
```

**HTTP Request:**

```http
GET http://localhost:8000/api/schedules?page=1
Headers:
  Authorization: Bearer 1|xyz123abc...
  Content-Type: application/json
```

↓

---

#### **Backend - Routes**

**File:** `backend/routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    // Schedules routes - read for all users, write for admin only
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/today', [ScheduleController::class, 'today']);
        Route::get('/upcoming', [ScheduleController::class, 'upcoming']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        
        // Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [ScheduleController::class, 'store']);
            Route::put('/{id}', [ScheduleController::class, 'update']);
            Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        });
    });
});
```

**Middleware checks:**
- ✅ User authenticated? (via `auth:sanctum`)
- ✅ For POST/PUT/DELETE: User is admin? (via `role:admin`)

↓

---

#### **Backend - ScheduleController**

**File:** `backend/app/Http/Controllers/Api/ScheduleController.php`

```php
public function index()
{
    // 1️⃣ Get all schedules with pagination
    $schedules = Schedule::orderBy('date', 'asc')
        ->orderBy('time', 'asc')
        ->paginate(10)
        ->through(function ($schedule) {
            // 2️⃣ Format response
            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => $schedule->date->format('d/m/Y'),
                'time' => $schedule->time,
                'description' => $schedule->description,
            ];
        });

    // 3️⃣ Return JSON response
    return response()->json([
        'data' => $schedules->items(),
        'total' => $schedules->total(),
        'per_page' => $schedules->perPage(),
        'current_page' => $schedules->currentPage(),
    ], 200);
}
```

↓

---

#### **Backend - Database Query**

**Eloquent ORM:**

```php
Schedule::orderBy('date', 'asc')
    ->orderBy('time', 'asc')
    ->paginate(10)
```

**Converts to SQL:**

```sql
SELECT * FROM schedules
ORDER BY date ASC, time ASC
LIMIT 10 OFFSET 0
```

**Database Structure:**

```
┌────────────────────────────────────────────────┐
│ schedules table                                │
├────────────────────────────────────────────────┤
│ id: 1                                          │
│ title: "Họp team tuần này"                     │
│ date: 2026-01-09 (stored as DATE)             │
│ time: 14:30 (stored as TIME)                   │
│ description: "Discuss Q1 targets at meeting room 1" │
│ created_at: 2026-01-07 10:30:00               │
│ updated_at: 2026-01-07 10:30:00               │
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
      "title": "Họp team tuần này",
      "date": "09/01/2026",
      "time": "14:30",
      "description": "Discuss Q1 targets at meeting room 1"
    },
    {
      "id": 2,
      "title": "Training: New HR System",
      "date": "10/01/2026",
      "time": "10:00",
      "description": "All employees must attend"
    },
    {
      "id": 3,
      "title": "One-on-one meeting",
      "date": "15/01/2026",
      "time": "15:00",
      "description": null
    }
  ],
  "total": 3,
  "per_page": 10,
  "current_page": 1
}
```

↓

---

#### **Frontend - Display Data**

```javascript
// Receive response
const res = await scheduleService.getSchedules(1)

// Update state
workSchedule.value = res.data  // Array of schedules

// Vue renders schedule list with title, date, time, description
```

---

### 2️⃣ **Lấy lịch hôm nay (GET /api/schedules/today)**

#### **Frontend**

```javascript
const getTodaySchedules = async () => {
  try {
    const res = await scheduleService.getTodaySchedules()
    console.log('Today schedules:', res.data)
  } catch (error) {
    console.error(error)
  }
}
```

#### **Backend Controller**

```php
public function today()
{
    $today = now()->format('Y-m-d');
    
    // 👇 Get today's schedules
    $schedules = Schedule::whereDate('date', $today)
        ->orderBy('time', 'asc')
        ->get()
        ->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => $schedule->date->format('d/m/Y'),
                'time' => $schedule->time,
                'description' => $schedule->description,
            ];
        });

    return response()->json([
        'data' => $schedules,
        'count' => $schedules->count(),
    ], 200);
}
```

#### **SQL Query**

```sql
SELECT * FROM schedules
WHERE DATE(date) = CURDATE()  -- Today's date
ORDER BY time ASC
```

#### **Response**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Morning standup",
      "date": "07/01/2026",
      "time": "09:00",
      "description": "Team daily sync"
    },
    {
      "id": 5,
      "title": "Lunch break",
      "date": "07/01/2026",
      "time": "12:00",
      "description": null
    }
  ],
  "count": 2
}
```

---

### 3️⃣ **Lấy lịch 7 ngày tới (GET /api/schedules/upcoming)**

#### **Backend Controller**

```php
public function upcoming()
{
    $today = now();
    $endDate = $today->copy()->addDays(7);
    
    // 👇 Get schedules for next 7 days
    $schedules = Schedule::whereBetween('date', [$today, $endDate])
        ->orderBy('date', 'asc')
        ->orderBy('time', 'asc')
        ->get()
        ->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => $schedule->date->format('d/m/Y'),
                'time' => $schedule->time,
                'description' => $schedule->description,
            ];
        });

    return response()->json([
        'data' => $schedules,
        'count' => $schedules->count(),
    ], 200);
}
```

#### **SQL Query**

```sql
SELECT * FROM schedules
WHERE date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY date ASC, time ASC
```

---

### 4️⃣ **Tạo lịch làm việc mới (POST /api/schedules) - ADMIN ONLY**

#### **Frontend - Admin Form**

```vue
<template>
  <form @submit.prevent="createSchedule">
    <div class="mb-3">
      <label>Tiêu đề</label>
      <input v-model="form.title" type="text" class="form-control" required />
    </div>
    <div class="mb-3">
      <label>Ngày</label>
      <input v-model="form.date" type="date" class="form-control" required />
    </div>
    <div class="mb-3">
      <label>Thời gian</label>
      <input v-model="form.time" type="time" class="form-control" required />
    </div>
    <div class="mb-3">
      <label>Mô tả</label>
      <textarea v-model="form.description" class="form-control"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Thêm lịch</button>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { scheduleService } from '../services/dashboardService'

const form = ref({
  title: '',
  date: '',
  time: '',
  description: ''
})

const createSchedule = async () => {
  try {
    const res = await scheduleService.createSchedule(form.value)
    console.log('Schedule created:', res)
    // Reset form
    form.value = { title: '', date: '', time: '', description: '' }
  } catch (error) {
    console.error('Failed to create schedule:', error)
  }
}
</script>
```

#### **Frontend - Service Call**

```javascript
async createSchedule(data) {
  try {
    // 👇 POST request with data
    const response = await apiClient.post('/api/schedules', data)
    return response.data
  } catch (error) {
    throw error
  }
}
```

#### **HTTP Request**

```http
POST http://localhost:8000/api/schedules
Headers:
  Authorization: Bearer 1|adminToken123...
  Content-Type: application/json

Body:
{
  "title": "Họp lãnh đạo",
  "date": "2026-01-15",
  "time": "14:30",
  "description": "Discuss Q1 strategy"
}
```

↓

---

#### **Backend - Route & Middleware**

```php
Route::middleware('auth:sanctum')->prefix('schedules')->group(function () {
    Route::middleware('role:admin')->post('/', [ScheduleController::class, 'store']);
});
```

**Checks:**
- ✅ User is authenticated? (auth:sanctum)
- ✅ User is admin? (role:admin)

↓

---

#### **Backend - ScheduleController**

```php
public function store(Request $request)
{
    // 1️⃣ Validate input
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'date' => 'required|date',
        'time' => 'required|date_format:H:i',
        'description' => 'nullable|string',
    ]);

    // 2️⃣ Create record in database
    $schedule = Schedule::create($validated);

    // 3️⃣ Return response
    return response()->json([
        'message' => 'Schedule created successfully',
        'data' => [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'date' => $schedule->date->format('d/m/Y'),
            'time' => $schedule->time,
            'description' => $schedule->description,
        ]
    ], 201);  // 201 = Created
}
```

#### **Backend - Schedule Model**

```php
class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'date',
        'time',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
    ];
}
```

#### **SQL Insert**

```sql
INSERT INTO schedules (title, date, time, description, created_at, updated_at)
VALUES ('Họp lãnh đạo', '2026-01-15', '14:30', 'Discuss Q1 strategy', NOW(), NOW())
```

#### **Response**

```json
{
  "message": "Schedule created successfully",
  "data": {
    "id": 10,
    "title": "Họp lãnh đạo",
    "date": "15/01/2026",
    "time": "14:30",
    "description": "Discuss Q1 strategy"
  }
}
```

---

### 5️⃣ **Cập nhật lịch (PUT /api/schedules/{id}) - ADMIN ONLY**

#### **Frontend - Edit Form**

```vue
<template>
  <form @submit.prevent="updateSchedule(schedule.id)">
    <div class="mb-3">
      <label>Tiêu đề</label>
      <input v-model="schedule.title" type="text" class="form-control" required />
    </div>
    <div class="mb-3">
      <label>Ngày</label>
      <input v-model="schedule.date" type="date" class="form-control" required />
    </div>
    <div class="mb-3">
      <label>Thời gian</label>
      <input v-model="schedule.time" type="time" class="form-control" required />
    </div>
    <div class="mb-3">
      <label>Mô tả</label>
      <textarea v-model="schedule.description" class="form-control"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Cập nhật</button>
  </form>
</template>

<script setup>
const updateSchedule = async (id) => {
  try {
    const res = await scheduleService.updateSchedule(id, schedule.value)
    console.log('Schedule updated:', res)
  } catch (error) {
    console.error('Failed to update:', error)
  }
}
</script>
```

#### **Frontend - Service**

```javascript
async updateSchedule(id, data) {
  try {
    // 👇 PUT request to update
    const response = await apiClient.put(`/api/schedules/${id}`, data)
    return response.data
  } catch (error) {
    throw error
  }
}
```

#### **HTTP Request**

```http
PUT http://localhost:8000/api/schedules/10
Headers:
  Authorization: Bearer 1|adminToken123...
  Content-Type: application/json

Body:
{
  "title": "Họp lãnh đạo (Rescheduled)",
  "date": "2026-01-16",
  "time": "15:00"
}
```

#### **Backend - ScheduleController**

```php
public function update(Request $request, $id)
{
    // 1️⃣ Find schedule
    $schedule = Schedule::findOrFail($id);
    
    // 2️⃣ Validate input
    $validated = $request->validate([
        'title' => 'sometimes|required|string|max:255',
        'date' => 'sometimes|required|date',
        'time' => 'sometimes|required|date_format:H:i',
        'description' => 'nullable|string',
    ]);

    // 3️⃣ Update record
    $schedule->update($validated);

    // 4️⃣ Return response
    return response()->json([
        'message' => 'Schedule updated successfully',
        'data' => [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'date' => $schedule->date->format('d/m/Y'),
            'time' => $schedule->time,
            'description' => $schedule->description,
        ]
    ], 200);
}
```

#### **SQL Update**

```sql
UPDATE schedules
SET title = 'Họp lãnh đạo (Rescheduled)',
    date = '2026-01-16',
    time = '15:00',
    updated_at = NOW()
WHERE id = 10
```

---

### 6️⃣ **Xóa lịch (DELETE /api/schedules/{id}) - ADMIN ONLY**
  
#### **Frontend - Delete Button**

```vue
<template>
  <button @click="confirmDelete(schedule.id)" class="btn btn-danger btn-sm">
    <i class="bi bi-trash"></i> Xóa
  </button>
</template>

<script setup>
const confirmDelete = async (id) => {
  if (!confirm('Bạn chắc chắn muốn xóa lịch này?')) return
  
  try {
    const res = await scheduleService.deleteSchedule(id)
    console.log('Schedule deleted:', res)
    // Reload list or remove from array
  } catch (error) {
    console.error('Failed to delete:', error)
  }
}
</script>
```

#### **Frontend - Service**

```javascript
async deleteSchedule(id) {
  try {
    // 👇 DELETE request
    const response = await apiClient.delete(`/api/schedules/${id}`)
    return response.data
  } catch (error) {
    throw error
  }
}
```

#### **HTTP Request**

```http
DELETE http://localhost:8000/api/schedules/10
Headers:
  Authorization: Bearer 1|adminToken123...
```

#### **Backend - ScheduleController**

```php
public function destroy($id)
{
    // 1️⃣ Find schedule
    $schedule = Schedule::findOrFail($id);
    
    // 2️⃣ Delete from database
    $schedule->delete();

    // 3️⃣ Return response
    return response()->json([
        'message' => 'Schedule deleted successfully',
    ], 200);
}
```

#### **SQL Delete**

```sql
DELETE FROM schedules WHERE id = 10
```

#### **Response**

```json
{
  "message": "Schedule deleted successfully"
}
```

---

### 7️⃣ **Lấy 1 lịch cụ thể (GET /api/schedules/{id})**

#### **Frontend**

```javascript
const loadScheduleDetail = async (id) => {
  try {
    const schedule = await scheduleService.getScheduleById(id)
    console.log('Schedule detail:', schedule)
  } catch (error) {
    console.error('Failed to load:', error)
  }
}
```

#### **Backend Controller**

```php
public function show($id)
{
    // 👇 Find and return single schedule
    $schedule = Schedule::findOrFail($id);

    return response()->json([
        'id' => $schedule->id,
        'title' => $schedule->title,
        'date' => $schedule->date->format('d/m/Y'),
        'time' => $schedule->time,
        'description' => $schedule->description,
    ], 200);
}
```

#### **Response**

```json
{
  "id": 10,
  "title": "Họp lãnh đạo",
  "date": "15/01/2026",
  "time": "14:30",
  "description": "Discuss Q1 strategy"
}
```

---

## 📋 Tóm tắt API Endpoints

| Method | Endpoint | Controller | Roles | Mục đích |
|--------|----------|-----------|-------|----------|
| GET | `/api/schedules` | `index()` | All | Danh sách lịch (paginated) |
| GET | `/api/schedules/today` | `today()` | All | Lịch hôm nay |
| GET | `/api/schedules/upcoming` | `upcoming()` | All | Lịch 7 ngày tới |
| GET | `/api/schedules/{id}` | `show($id)` | All | Chi tiết 1 lịch |
| POST | `/api/schedules` | `store()` | Admin | Tạo lịch mới |
| PUT | `/api/schedules/{id}` | `update($id)` | Admin | Cập nhật lịch |
| DELETE | `/api/schedules/{id}` | `destroy($id)` | Admin | Xóa lịch |

---

## 🗄️ Database Schema

**Table:** `schedules`

```sql
CREATE TABLE schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,              -- Event title
    date DATE NOT NULL,                       -- Event date
    time TIME NOT NULL,                       -- Event time (HH:ii format)
    description TEXT NULL,                    -- Optional description
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_date (date),
    INDEX idx_date_time (date, time)
);
```

**Column Details:**

```
┌────────────────────────────────────────────────┐
│ schedules table                                │
├────────────────────────────────────────────────┤
│ id                                             │
│ ├─ Type: BIGINT UNSIGNED                      │
│ ├─ Primary Key                                 │
│ └─ Auto Increment                              │
│                                                │
│ title                                          │
│ ├─ Type: VARCHAR(255)                          │
│ ├─ NOT NULL                                    │
│ └─ Example: "Họp team", "Training session"    │
│                                                │
│ date                                           │
│ ├─ Type: DATE                                  │
│ ├─ NOT NULL                                    │
│ └─ Example: 2026-01-15                         │
│                                                │
│ time                                           │
│ ├─ Type: TIME                                  │
│ ├─ NOT NULL                                    │
│ └─ Example: 14:30 (2:30 PM)                    │
│                                                │
│ description                                    │
│ ├─ Type: TEXT                                  │
│ ├─ NULL (optional)                             │
│ └─ Example: "Discuss Q1 targets"              │
│                                                │
│ Indexes:                                       │
│ ├─ idx_date: For ordering/filtering by date  │
│ └─ idx_date_time: For compound queries         │
└────────────────────────────────────────────────┘
```

---

## 🔐 Security & Authorization

### **Authentication Layer**
- All endpoints require `auth:sanctum` middleware
- Bearer token must be sent in Authorization header
- Token validation happens before any controller method executes

### **Authorization Layer - Role-based**

**Public Access (All authenticated users):**
- ✅ GET `/api/schedules` - view all schedules
- ✅ GET `/api/schedules/today` - view today's schedules
- ✅ GET `/api/schedules/upcoming` - view upcoming schedules
- ✅ GET `/api/schedules/{id}` - view single schedule

**Admin Only:**
- ✅ POST `/api/schedules` - create new schedule
- ✅ PUT `/api/schedules/{id}` - edit schedule
- ✅ DELETE `/api/schedules/{id}` - delete schedule

**Middleware Check:**
```php
Route::middleware('role:admin')->group(function () {
    // These routes only accessible if user role = 'admin'
    Route::post('/', [ScheduleController::class, 'store']);
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
});
```

### **Data Validation**
```php
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'date' => 'required|date',
    'time' => 'required|date_format:H:i',
    'description' => 'nullable|string',
]);
```

---

## ✅ Tóm tắt luồng hoàn chỉnh

```
┌─────────────────────────────────────────────────────────────┐
│ EMPLOYEE - VIEW SCHEDULES                                   │
└─────────────────────────────────────────────────────────────┘
User opens Dashboard
   ↓
Vue component mounted → load schedules
   ↓
GET /api/schedules → ScheduleController.index()
   ↓
Query DB: SELECT * FROM schedules ORDER BY date, time
   ↓
Format & return JSON response
   ↓
Frontend displays schedule list with title, date, time
   ✅ Employee can view but NOT create/edit/delete


┌─────────────────────────────────────────────────────────────┐
│ ADMIN - CREATE SCHEDULE                                     │
└─────────────────────────────────────────────────────────────┘
Admin submits form
   ↓
POST /api/schedules (with title, date, time, description)
   ↓
Middleware: Check auth:sanctum ✅ + role:admin ✅
   ↓
ScheduleController.store() - Validate input
   ↓
INSERT INTO schedules VALUES (...)
   ↓
Return JSON with created schedule + id
   ↓
Frontend shows success notification & reloads list
   ✅ Schedule added to database


┌─────────────────────────────────────────────────────────────┐
│ ADMIN - EDIT SCHEDULE                                       │
└─────────────────────────────────────────────────────────────┘
Admin clicks edit button
   ↓
Form pre-filled with GET /api/schedules/{id}
   ↓
Admin updates fields & submits
   ↓
PUT /api/schedules/{id} (with new data)
   ↓
Middleware: Check auth:sanctum ✅ + role:admin ✅
   ↓
ScheduleController.update() - Validate input
   ↓
UPDATE schedules SET ... WHERE id = X
   ↓
Return JSON with updated schedule
   ↓
Frontend shows success notification
   ✅ Schedule updated


┌─────────────────────────────────────────────────────────────┐
│ ADMIN - DELETE SCHEDULE                                     │
└─────────────────────────────────────────────────────────────┘
Admin clicks delete button → Confirm dialog
   ↓
DELETE /api/schedules/{id}
   ↓
Middleware: Check auth:sanctum ✅ + role:admin ✅
   ↓
ScheduleController.destroy() - Find & delete
   ↓
DELETE FROM schedules WHERE id = X
   ↓
Return success message
   ↓
Frontend removes item from list
   ✅ Schedule deleted from database
```

---

## 🎯 Key Distinctions

| Feature | Employee | Admin |
|---------|----------|-------|
| View all schedules | ✅ | ✅ |
| View today's schedules | ✅ | ✅ |
| View upcoming schedules | ✅ | ✅ |
| Create schedule | ❌ Blocked by middleware | ✅ |
| Edit schedule | ❌ Blocked by middleware | ✅ |
| Delete schedule | ❌ Blocked by middleware | ✅ |

Toàn bộ quá trình đảm bảo **role-based access control**, **input validation**, và **efficient database queries**! 🎯
