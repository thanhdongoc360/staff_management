# 🔔 Luồng hoạt động NOTIFICATIONS - Frontend → Backend → Database

## 📊 Sơ đồ tổng quát

```
┌───────────────────────────────────────────────────────────────┐
│                    USER ACTION                                │
│  Mở trang /notifications hoặc xem bell icon trên header      │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. NotificationsView.vue                                      │
│    - onMounted() → load() + loadUnread()                      │
│    - Call notificationsService                                │
│                          ↓                                    │
│ 2. Notifications Service                                      │
│    - list(page) → GET /api/notifications?page=N               │
│    - unreadCount() → GET /api/notifications/unread-count      │
│    - markAsRead(id) → POST /api/notifications/{id}/mark-as-read │
│    - markAllAsRead() → POST /api/notifications/mark-all-as-read │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Request (Bearer token)
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 3. Routes (api.php) - auth:sanctum middleware                 │
│    - GET  /api/notifications                                  │
│    - GET  /api/notifications/unread-count                     │
│    - POST /api/notifications/{id}/mark-as-read                │
│    - POST /api/notifications/mark-all-as-read                 │
│                          ↓                                    │
│ 4. NotificationController                                     │
│    - index(): paginate notifications của user                 │
│    - unreadCount(): đếm is_read = false                       │
│    - markAsRead($id): update is_read = true                   │
│    - markAllAsRead(): update all is_read = true               │
│                          ↓                                    │
│ 5. Notification Model                                         │
│    - Eloquent: auth()->user()->notifications()                │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 6. notifications table                                        │
│    - id, user_id (FK), title, content, date, is_read         │
│    - created_at, updated_at                                   │
│                          ↓                                    │
│ 7. Query examples:                                            │
│    SELECT * FROM notifications WHERE user_id = X              │
│      ORDER BY created_at DESC LIMIT 10                        │
│    SELECT COUNT(*) FROM notifications                         │
│      WHERE user_id = X AND is_read = 0                        │
│    UPDATE notifications SET is_read = 1 WHERE id = Y          │
└───────────────────────────────────────────────────────────────┘
                          ↓
            ✅ Frontend displays notifications
```

---

## 🔄 Chi tiết từng luồng

### 1️⃣ **Lấy danh sách thông báo (GET /api/notifications)**

#### **Frontend - User Interface**

**File:** `frontend/src/views/NotificationsView.vue`

```vue
<script setup>
import { onMounted, ref } from 'vue'
import { notificationsService } from '../services/notificationsService'

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const unreadCount = ref(0)
const loading = ref(false)

const load = async (page = 1) => {
  loading.value = true
  try {
    const res = await notificationsService.list(page)
    // 👇 Nhận dữ liệu từ backend
    items.value = res.data           // Array notifications
    meta.value = res.meta            // Pagination
    unreadCount.value = res.unread_count  // Số chưa đọc
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  load()
})
</script>

<template>
  <table>
    <tr v-for="item in items" :key="item.id">
      <td>{{ item.title }}</td>
      <td>{{ item.content }}</td>
      <td>{{ item.date }}</td>
      <td>
        <span :class="item.is_read ? 'bg-secondary' : 'bg-primary'">
          {{ item.is_read ? 'Đã đọc' : 'Chưa đọc' }}
        </span>
      </td>
    </tr>
  </table>
</template>
```

**User opens /notifications** → Trigger `onMounted()` → `load()` ↓

---

#### **Frontend - Notifications Service**

**File:** `frontend/src/services/notificationsService.js`

```javascript
import apiClient from './api'

export const notificationsService = {
  async list(page = 1) {
    // 👇 HTTP GET request
    const response = await apiClient.get(`/api/notifications?page=${page}`)
    return response.data  // { data: [...], meta: {...}, unread_count: N }
  },

  async unreadCount() {
    const response = await apiClient.get('/api/notifications/unread-count')
    return response.data.unread_count
  }
}
```

**HTTP Request:**

```http
GET http://localhost:8000/api/notifications?page=1
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
    // Notifications routes (for all authenticated users)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });
});
```

**Middleware check:**
- ✅ User authenticated? (via `auth:sanctum`)
- ✅ Get user from token

↓

---

#### **Backend - NotificationController**

**File:** `backend/app/Http/Controllers/Api/NotificationController.php`

```php
public function index()
{
    // 1️⃣ Get notifications of authenticated user with pagination
    $notifications = auth()->user()->notifications()
        ->orderBy('created_at', 'desc')
        ->paginate(10)
        ->through(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'content' => $notification->content,
                'date' => $notification->date->format('d/m/Y H:i'),
                'is_read' => $notification->is_read,
            ];
        });

    // 2️⃣ Count unread notifications
    $unread = auth()->user()->notifications()
        ->where('is_read', false)
        ->count();

    // 3️⃣ Return JSON response
    return response()->json([
        'data' => $notifications->items(),
        'meta' => [
            'total' => $notifications->total(),
            'per_page' => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
        ],
        'unread_count' => $unread,
    ], 200);
}
```

↓

---

#### **Backend - Database Query**

**Eloquent ORM:**

```php
auth()->user()->notifications()
    ->orderBy('created_at', 'desc')
    ->paginate(10)
```

**Converts to SQL:**

```sql
SELECT * FROM notifications
WHERE user_id = 1  -- authenticated user
ORDER BY created_at DESC
LIMIT 10 OFFSET 0
```

**Database Structure:**

```
┌────────────────────────────────────────────────┐
│ notifications table                            │
├────────────────────────────────────────────────┤
│ id: 1                                          │
│ user_id: 1 (FK → users)                        │
│ title: "Đơn nghỉ phép đã được duyệt"          │
│ content: "Đơn nghỉ phép từ 20/12 đã duyệt"    │
│ date: 2025-12-08                               │
│ is_read: true (boolean)                        │
│ created_at: 2025-12-08 10:30:00               │
│ updated_at: 2025-12-08 14:20:00               │
└────────────────────────────────────────────────┘
```

↓

---

#### **Backend - JSON Response**

```json
{
  "data": [
    {
      "id": 3,
      "title": "Thông báo: Họp team vào thứ 5 tuần này lúc 2PM",
      "content": "Thông báo: Họp team vào thứ 5 tuần này lúc 2PM",
      "date": "03/12/2025 00:00",
      "is_read": false
    },
    {
      "id": 2,
      "title": "Nhắc nhở: Nộp báo cáo tháng 12 trước ngày 15/12",
      "content": "Nhắc nhở: Nộp báo cáo tháng 12 trước ngày 15/12",
      "date": "05/12/2025 00:00",
      "is_read": true
    }
  ],
  "meta": {
    "total": 3,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1
  },
  "unread_count": 1
}
```

↓

---

#### **Frontend - Display Data**

```javascript
// Receive response
const res = await notificationsService.list(1)

// Update state
items.value = res.data           // Array of notifications
meta.value = res.meta            // Pagination info
unreadCount.value = res.unread_count  // Unread count badge
```

**Vue renders:**
- Table with notifications
- Badge showing unread count
- "Đã đọc" / "Chưa đọc" status per item

---

### 2️⃣ **Đếm thông báo chưa đọc (GET /api/notifications/unread-count)**

#### **Frontend**

```javascript
const loadUnread = async () => {
  try {
    unreadCount.value = await notificationsService.unreadCount()
  } catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  loadUnread()
})
```

#### **Backend Controller**

```php
public function unreadCount()
{
    $count = auth()->user()->notifications()
        ->where('is_read', false)
        ->count();

    return response()->json([
        'unread_count' => $count,
    ], 200);
}
```

#### **SQL Query**

```sql
SELECT COUNT(*) FROM notifications
WHERE user_id = 1 AND is_read = 0
```

#### **Response**

```json
{
  "unread_count": 1
}
```

---

### 3️⃣ **Đánh dấu 1 thông báo đã đọc (POST /api/notifications/{id}/mark-as-read)**

#### **Frontend - User clicks "Đánh dấu đã đọc"**

```vue
<template>
  <button @click="markOne(item)" :disabled="item.is_read">
    <i class="bi bi-check"></i> Đánh dấu đã đọc
  </button>
</template>

<script setup>
const markOne = async (item) => {
  if (item.is_read) return
  try {
    await notificationsService.markAsRead(item.id)
    // 👇 Update local state
    item.is_read = true
    if (unreadCount.value > 0) unreadCount.value -= 1
  } catch (err) {
    console.error(err)
  }
}
</script>
```

#### **Service**

```javascript
async markAsRead(id) {
  const response = await apiClient.post(`/api/notifications/${id}/mark-as-read`)
  return response.data
}
```

#### **HTTP Request**

```http
POST http://localhost:8000/api/notifications/3/mark-as-read
Headers:
  Authorization: Bearer 1|xyz123abc...
Body: (empty)
```

#### **Backend Controller**

```php
public function markAsRead($id)
{
    $notification = auth()->user()->notifications()->findOrFail($id);
    $notification->update(['is_read' => true]);

    return response()->json([
        'message' => 'Notification marked as read',
    ], 200);
}
```

#### **SQL Update**

```sql
UPDATE notifications
SET is_read = 1, updated_at = NOW()
WHERE id = 3 AND user_id = 1
```

#### **Response**

```json
{
  "message": "Notification marked as read"
}
```

---

### 4️⃣ **Đánh dấu TẤT CẢ đã đọc (POST /api/notifications/mark-all-as-read)**

#### **Frontend - User clicks "Đánh dấu tất cả đã đọc"**

```vue
<template>
  <button @click="markAll" :disabled="unreadCount === 0">
    <i class="bi bi-check-all"></i> Đánh dấu tất cả đã đọc
  </button>
</template>

<script setup>
const markAll = async () => {
  try {
    await notificationsService.markAllAsRead()
    // 👇 Update all local items
    items.value = items.value.map((n) => ({ ...n, is_read: true }))
    unreadCount.value = 0
  } catch (err) {
    console.error(err)
  }
}
</script>
```

#### **Service**

```javascript
async markAllAsRead() {
  const response = await apiClient.post('/api/notifications/mark-all-as-read')
  return response.data
}
```

#### **Backend Controller**

```php
public function markAllAsRead()
{
    auth()->user()->notifications()->update(['is_read' => true]);

    return response()->json([
        'message' => 'All notifications marked as read',
    ], 200);
}
```

#### **SQL Update**

```sql
UPDATE notifications
SET is_read = 1, updated_at = NOW()
WHERE user_id = 1
```

#### **Response**

```json
{
  "message": "All notifications marked as read"
}
```

---

## 🎯 Real-time Notifications (Laravel Echo + WebSocket)

### **Frontend - Setup Echo Listener**

**File:** `frontend/src/views/NotificationsView.vue`

```javascript
import { getEcho } from '../echo'

let channel = null

const setupRealtime = () => {
  const echo = getEcho()
  if (!echo || !authStore.user?.id) return

  // Listen to private channel
  channel = echo.private(`notifications.${authStore.user.id}`)
    .listen('.notification.created', (payload) => {
      // 👇 New notification received
      items.value.unshift(payload)  // Add to top of list
      unreadCount.value += 1         // Increment badge
    })
}

onMounted(() => {
  setupRealtime()
})

onBeforeUnmount(() => {
  if (channel) channel.unsubscribe()
})
```

### **Backend - Broadcast Event**

**File:** `backend/app/Models/Notification.php`

```php
protected static function booted()
{
    static::created(function (Notification $notification) {
        // 👇 Broadcast event when notification created
        event(new NewNotificationEvent($notification));
    });
}
```

**File:** `backend/app/Events/NewNotificationEvent.php`

```php
class NewNotificationEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return new PrivateChannel('notifications.' . $this->notification->user_id);
    }
}
```

**WebSocket Flow:**

```
Backend creates notification
   ↓
Model fires 'created' event
   ↓
NewNotificationEvent broadcast via Laravel Echo Server
   ↓
Frontend Echo listener receives event
   ↓
Vue component updates UI instantly (no refresh needed)
```

---

## 📋 Tóm tắt API Endpoints

| Method | Endpoint | Controller Method | Mục đích |
|--------|----------|-------------------|----------|
| GET | `/api/notifications` | `index()` | Lấy danh sách thông báo (paginated) |
| GET | `/api/notifications/unread-count` | `unreadCount()` | Đếm số thông báo chưa đọc |
| GET | `/api/notifications/recent` | `recent()` | Lấy 5 thông báo gần nhất |
| POST | `/api/notifications/{id}/mark-as-read` | `markAsRead($id)` | Đánh dấu 1 thông báo đã đọc |
| POST | `/api/notifications/mark-all-as-read` | `markAllAsRead()` | Đánh dấu tất cả đã đọc |

---

## 🗄️ Database Schema

**Table:** `notifications`

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,  -- FK to users
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    date DATE NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);
```

**Relationships:**

- `Notification belongsTo User`
- `User hasMany Notifications`

---

## 🔐 Security & Authorization

1. **Middleware:** All endpoints require `auth:sanctum` → Bearer token
2. **Scope:** User chỉ thấy notifications của chính mình
   - `auth()->user()->notifications()` → auto filter by user_id
3. **findOrFail:** Khi mark as read, chỉ tìm trong notifications của user
   - `auth()->user()->notifications()->findOrFail($id)`
   - ❌ Không thể mark notification của user khác

---

## ✅ Tóm tắt luồng

```
User vào /notifications
   ↓
GET /api/notifications → paginate notifications của user
   ↓
Display table với status "Đã đọc" / "Chưa đọc"
   ↓
User click "Đánh dấu đã đọc"
   ↓
POST /api/notifications/{id}/mark-as-read → UPDATE is_read = true
   ↓
Frontend cập nhật local state → Badge giảm 1
   ↓
Real-time: Echo listen private channel → new notification push tự động
```

Toàn bộ quá trình đảm bảo **security**, **real-time updates**, và **user isolation**! 🎯
