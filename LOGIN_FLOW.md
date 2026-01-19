# 🔐 Luồng hoạt động LOGIN - Frontend → Backend → Database

## 📊 Sơ đồ tổng quát

```    
┌───────────────────────────────────────────────────────────────┐
│                    USER ACTION                                │
│  Nhập email & password → Click "Đăng nhập"                    │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  FRONTEND (Vue.js)                            │
├───────────────────────────────────────────────────────────────┤
│ 1. LoginView.vue                                              │
│    - Validate form                                            │
│    - Call handleLogin()                                       │
│                          ↓                                    │
│ 2. Auth Store (Pinia)                                         │
│    - authStore.login(credentials)                             │
│                          ↓                                    │
│ 3. Auth Service                                               │
│    - authService.login(credentials)                           │
│    - apiClient.post('/api/login', {email, password})          │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP POST Request
                  ────────────────────
                  POST /api/login
                  Body: {
                    "email": "admin@example.com",
                    "password": "password123"
                  }
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel)                            │
├───────────────────────────────────────────────────────────────┤
│ 4. Routes (api.php)                                           │
│    - Match: POST /login → AuthController@login                │
│                          ↓                                    │
│ 5. AuthController                                             │
│    - Validate email & password                                │
│    - Query database for user                                  │
│                          ↓                                    │
│ 6. User Model (Eloquent ORM)                                  │
│    - User::where('email', $email)->first()                    │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE (MySQL)                             │
├───────────────────────────────────────────────────────────────┤
│ 7. Query users table                                          │
│    SELECT * FROM users WHERE email = 'admin@example.com'      │
│                          ↓                                    │
│ 8. Return user record                                         │
│    {                                                          │
│      id: 1,                                                   │
│      name: 'Admin',                                           │
│      email: 'admin@example.com',                              │
│      password: '$2y$10$hashed...',                            │
│      role: 'admin'                                            │
│    }                                                          │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
┌───────────────────────────────────────────────────────────────┐
│              BACKEND - Password Verification                  │
├───────────────────────────────────────────────────────────────┤
│ 9. Hash::check($password, $user->password)                    │
│    - Compare input password with hashed password              │
│    ✅ Match? Continue                                         │
│    ❌ Fail? Return 422 error                                  │
│                          ↓                                    │
│ 10. Create API Token (Laravel Sanctum)                        │
│     $token = $user->createToken('auth_token')                 │
│                          ↓                                    │
│ 11. Insert to personal_access_tokens table                    │
│     INSERT INTO personal_access_tokens                        │
│     (tokenable_id, name, token, abilities)                    │
│     VALUES (1, 'auth_token', 'hashed_token', '["*"]')         │
└─────────────────────────┬─────────────────────────────────────┘
                          ↓
                  HTTP Response (JSON)
                  ────────────────────
                  Status: 200
                  Body: {
                    "token": "1|xyz123abc...",
                    "user": {
                      "id": 1,
                      "name": "Admin",
                      "email": "admin@example.com",
                      "role": "admin"
                    },
                    "message": "Login successfully"
                  }
                          ↓
┌───────────────────────────────────────────────────────────────┐
│              FRONTEND - Save Token & User                     │
├───────────────────────────────────────────────────────────────┤
│ 12. Auth Store receives response                              │
│     - this.token = data.token                                 │
│     - this.user = data.user                                   │
│     - this.isAuthenticated = true                             │
│                          ↓                                    │
│ 13. Save to localStorage                                      │
│     - localStorage.setItem('token', token)                    │
│     - localStorage.setItem('user', JSON.stringify(user))      │
│                          ↓                                    │
│ 14. Redirect to Dashboard                                     │
│     - router.push('/dashboard')                               │
└───────────────────────────────────────────────────────────────┘
                          ↓
                  ✅ USER LOGGED IN
```

---

## 🔍 Chi tiết từng bước

### **BƯỚC 1: Frontend - User Input**

**File:** `frontend/src/views/LoginView.vue`

```vue
<template>
  <div class="login-page">
    <form @submit.prevent="handleLogin">  
      <!-- Khi bấm nút → gọi handleLogin() -->
      <!-- Email input -->
      <input 
        v-model="credentials.email" 
        type="email" 
        placeholder="your.email@staffhub.com"
        required
      />
      
      <!-- Password input -->
      <input 
        v-model="credentials.password" 
        type="password" 
        placeholder="Nhập mật khẩu"
        required
      />
      
      <!-- Submit button -->
      <button type="submit" :disabled="loading">
        Đăng nhập
      </button>
      
      <!-- Error message -->
      <div v-if="error" class="alert alert-danger">
        {{ error }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Data binding
const credentials = ref({
  email: '',         // Input: admin@example.com
  password: ''       // Input: password123
})

const loading = ref(false)
const error = ref('')

// Handle form submit
const handleLogin = async () => {  // gọi khi ấn nút đăng nhập
  loading.value = true
  error.value = ''
  
  try {
    // 👇 Call Auth Store
    await authStore.login(credentials.value)
    
    // ✅ Success → Redirect
    router.push('/dashboard')
    
  } catch (err) {
    // ❌ Error → Show message
    error.value = err.response?.data?.message || 'Đăng nhập thất bại'
  } finally {
    loading.value = false
  }
}
</script>
```

**User clicks "Đăng nhập"** → Trigger `handleLogin()` ↓

---

### **BƯỚC 2: Frontend - Auth Store (Pinia)**

**File:** `frontend/src/stores/auth.js`

```javascript
import { defineStore } from 'pinia'
import authService from '../services/authService'

export const useAuthStore = defineStore('auth', {
  // State
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
  }),

  // Getters
  getters: {
    currentUser: (state) => state.user,
    isAdmin: (state) => state.user?.role === 'admin',
    isEmployee: (state) => state.user?.role === 'employee',
  },

  // Actions
  actions: {
    async login(credentials) {
      try {
        // 👇 Call Auth Service
        const data = await authService.login(credentials)
        //     ↑ credentials = { email: "admin@example.com", password: "password123" }
        
        // 👇 Save to state
        this.token = data.token                    // "1|xyz123abc..."
        this.user = data.user                      // { id: 1, name: "Admin", ... }
        this.isAuthenticated = true
        
        // 👇 Save to localStorage (persistent)
        localStorage.setItem('token', data.token)
        localStorage.setItem('user', JSON.stringify(data.user))
        
        return data
        
      } catch (error) {
        throw error  // Pass error to LoginView
      }
    }
  }
})
```

↓

---

### **BƯỚC 3: Frontend - Auth Service**

**File:** `frontend/src/services/authService.js`

```javascript
import apiClient from './api'

const authService = {
  async login(credentials) {
    // 👇 HTTP POST request to backend
    const response = await apiClient.post('/api/login', credentials)
    //                                     ↑ endpoint
    //                                     ↑ body: { email, password }
    
    return response.data  // 👈 Return backend response
  }
}

export default authService
```

**API Client configuration:**

**File:** `frontend/src/services/api.js`

```javascript
import axios from 'axios'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor (add token)
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  }
)

export default apiClient
```

**HTTP Request được gửi:**

```http
POST http://localhost:8000/api/login
Content-Type: application/json
Accept: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}
```

↓

---
=> Auth Service chỉ làm 1 việc: gọi API và trả data — không state, không UI, không logic thừa

### **BƯỚC 4: Backend - Routes**

**File:** `backend/routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/login', [AuthController::class, 'login']);
//           ↑ Match POST /api/login
//                         ↑ Call AuthController@login method
```

↓

---

### **BƯỚC 5: Backend - AuthController**

**File:** `backend/app/Http/Controllers/Api/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1️⃣ Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        // Input data:
        // $request->email = "admin@example.com"
        // $request->password = "password123"
        
        // 2️⃣ Query database for user
        $user = User::where('email', $request->email)->first();
        //      ↓ Eloquent ORM → SQL query
        
        // 3️⃣ Verify user exists and password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        //      ↑ Hash::check() compares plain password with hashed password
        
        // 4️⃣ Create API token (Laravel Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;
        //             ↑ Generate unique token for this user session
        
        // 5️⃣ Return JSON response
        return response()->json([
            'token' => $token,                    // "1|xyz123abc..."
            'user' => [
                'id' => $user->id,                // 1
                'name' => $user->name,            // "Admin"
                'email' => $user->email,          // "admin@example.com"
                'role' => $user->role,            // "admin"
            ],
            'message' => 'Login successfully',
        ], 200);
    }
}
```

↓

---

### **BƯỚC 6: Backend - User Model**

**File:** `backend/app/Models/User.php`

```php     
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;  // 👈 Enable token creation
    
    protected $fillable = ['name', 'email', 'password', 'role'];
    
    protected $hidden = ['password', 'remember_token'];
    
    // Relationships
    public function employee() {
        return $this->hasOne(Employee::class);
    }
    
    // Helper methods
    public function isAdmin() {
        return $this->role === 'admin';
    }
}
```

**Eloquent Query:**

```php
$user = User::where('email', $request->email)->first();
```

**Converts to SQL:**

```sql
SELECT * FROM users 
WHERE email = 'admin@example.com' 
LIMIT 1
```

↓

---

### **BƯỚC 7: Database - Query Execution**

**Database:** MySQL

**Table:** `users`

```
┌────┬──────────┬──────────────────────┬──────────────────────┬────────┬────────────────────────┐
│ id │ name     │ email                │ password             │ role   │ created_at             │
├────┼──────────┼──────────────────────┼──────────────────────┼────────┼────────────────────────┤
│ 1  │ Admin    │ admin@example.com    │ $2y$10$hashed...    │ admin  │ 2026-01-01 10:00:00   │
│ 2  │ John Doe │ john@example.com     │ $2y$10$hashed...    │ employee│ 2026-01-01 11:00:00   │
└────┴──────────┴──────────────────────┴──────────────────────┴────────┴────────────────────────┘
```

**Query result:**

```php
$user = [
    'id' => 1,
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'role' => 'admin',
    'email_verified_at' => null,
    'remember_token' => null,
    'created_at' => '2026-01-01 10:00:00',
    'updated_at' => '2026-01-01 10:00:00',
]
```

↓

---

### **BƯỚC 8: Backend - Password Verification**

```php
// Input password (plain text)
$inputPassword = "password123";

// Database password (hashed)
$hashedPassword = "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi";

// Verify password
$isValid = Hash::check($inputPassword, $hashedPassword);
//         ↓ Bcrypt algorithm comparison
//         ↑ Returns true or false

if (!$isValid) {
    // ❌ Wrong password
    throw ValidationException::withMessages([
        'email' => ['The provided credentials are incorrect.'],
    ]);
}

// ✅ Password correct → Continue
```

↓

---

### **BƯỚC 9: Backend - Create API Token**

**Laravel Sanctum** creates token:

```php
$token = $user->createToken('auth_token')->plainTextToken;
//       ↓ This method does:
//       1. Generate random token string
//       2. Hash token
//       3. Insert to personal_access_tokens table
//       4. Return plain token (only time it's visible)
```

**SQL Insert:**

```sql
INSERT INTO personal_access_tokens 
  (tokenable_type, tokenable_id, name, token, abilities, created_at, updated_at)
VALUES 
  ('App\\Models\\User', 1, 'auth_token', 'hashed_token_xyz', '["*"]', NOW(), NOW())
```

**Table:** `personal_access_tokens`

```
┌────┬──────────────────┬──────────────┬────────────┬─────────────────────────┬────────────┐
│ id │ tokenable_type   │ tokenable_id │ name       │ token (hashed)          │ abilities  │
├────┼──────────────────┼──────────────┼────────────┼─────────────────────────┼────────────┤
│ 1  │ App\Models\User  │ 1            │ auth_token │ sha256_hashed_token_xyz │ ["*"]      │
└────┴──────────────────┴──────────────┴────────────┴─────────────────────────┴────────────┘
```

**Returned token (plain text):**

```
1|xyz123abc456def789ghi...
↑  ↑
│  └─ Random token string
└─ Token ID
```

↓

---

### **BƯỚC 10: Backend - JSON Response**

```php
return response()->json([
    'token' => '1|xyz123abc456def789ghi...',
    'user' => [
        'id' => 1,
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'role' => 'admin',
    ],
    'message' => 'Login successfully',
], 200);
```

**HTTP Response:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "token": "1|xyz123abc456def789ghi...",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin"
  },
  "message": "Login successfully"
}
```

↓

---

### **BƯỚC 11: Frontend - Receive Response**

**Auth Store receives data:**

```javascript
// authService.login() returns response.data
const data = await authService.login(credentials)

// data = {
//   token: "1|xyz123abc456def789ghi...",
//   user: {
//     id: 1,
//     name: "Admin",
//     email: "admin@example.com",
//     role: "admin"
//   },
//   message: "Login successfully"
// }

// Save to state
this.token = data.token
this.user = data.user
this.isAuthenticated = true
```

↓

---

### **BƯỚC 12: Frontend - Save to localStorage**

```javascript
// Save token (used for subsequent API requests)
localStorage.setItem('token', data.token)
// localStorage['token'] = "1|xyz123abc456def789ghi..."

// Save user info (used for UI display)
localStorage.setItem('user', JSON.stringify(data.user))
// localStorage['user'] = '{"id":1,"name":"Admin","email":"admin@example.com","role":"admin"}'
```

**localStorage content:**

```javascript
{
  "token": "1|xyz123abc456def789ghi...",
  "user": "{\"id\":1,\"name\":\"Admin\",\"email\":\"admin@example.com\",\"role\":\"admin\"}"
}
```

↓

---

### **BƯỚC 13: Frontend - Redirect**

**LoginView.vue:**

```javascript
try {
  await authStore.login(credentials.value)
  
  // ✅ Login success
  router.push('/dashboard')  // 👈 Redirect to dashboard
  
} catch (err) {
  // ❌ Login failed
  error.value = err.response?.data?.message
}
```

**Router navigates to:** `/dashboard`

↓

---

### **BƯỚC 14: Subsequent Requests - Token Authentication**

**Every API request after login includes token:**

**Example:** Get salaries

```javascript
// Frontend
const response = await apiClient.get('/api/salaries')

// apiClient interceptor automatically adds:
// Headers: {
//   Authorization: "Bearer 1|xyz123abc456def789ghi..."
// }
```

**Backend verification:**

```php
// Route with auth middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/salaries', [SalaryController::class, 'index']);
});

// Laravel Sanctum:
// 1. Extract token from Authorization header
// 2. Query personal_access_tokens table
// 3. Find matching hashed token
// 4. Get user from tokenable_id
// 5. Set $request->user() to authenticated user
```

**SQL Query:**

```sql
SELECT users.* 
FROM users 
INNER JOIN personal_access_tokens 
  ON users.id = personal_access_tokens.tokenable_id 
WHERE personal_access_tokens.token = SHA256('xyz123abc456def789ghi...')
  AND personal_access_tokens.tokenable_type = 'App\\Models\\User'
```

---

## 📋 Tóm tắt Data Flow

| Bước | Nơi | Dữ liệu |
|------|-----|---------|
| 1 | Frontend Input | `{ email: "admin@example.com", password: "password123" }` |
| 2 | Auth Store | Call `authService.login()` |
| 3 | Auth Service | `POST /api/login` với body |
| 4 | Backend Routes | Route to `AuthController@login` |
| 5 | AuthController | Validate & query database |
| 6 | User Model | `User::where('email', ...)->first()` |
| 7 | Database | `SELECT * FROM users WHERE email = ...` |
| 8 | Backend | `Hash::check()` verify password |
| 9 | Backend | Create token via Sanctum |
| 10 | Database | `INSERT INTO personal_access_tokens` |
| 11 | Backend Response | `{ token: "1|...", user: {...} }` |
| 12 | Auth Store | Save token & user to state |
| 13 | localStorage | Persist token & user |
| 14 | Router | Redirect to `/dashboard` |

---

## 🔐 Security Features

1. **Password Hashing (Bcrypt)**
   - Never store plain passwords
   - Hash with `Hash::make()`
   - Verify with `Hash::check()`

2. **Token-based Auth (Laravel Sanctum)**
   - Stateless authentication
   - Token stored in `personal_access_tokens` table
   - Token included in `Authorization: Bearer <token>` header

3. **HTTPS (Production)**
   - Encrypt all communication
   - Prevent token interception

4. **Validation**
   - Email format validation
   - Required fields
   - Error messages

---

## ❌ Error Cases

### **Wrong Password:**

```php
// Backend returns:
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
// Status: 422 Unprocessable Entity
```

### **User Not Found:**

```php
// Same response as wrong password (security: don't reveal if user exists)
```

### **Missing Fields:**

```php
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
// Status: 422
```

---

## ✅ Success Flow Summary

```
User Input → Form Submit → Auth Store → Auth Service → HTTP POST
   ↓
Backend Routes → Controller → Validate → Query DB → Verify Password
   ↓
Create Token → Insert Token to DB → Return JSON Response
   ↓
Frontend Receives → Save to State → Save to localStorage → Redirect
   ↓
USER LOGGED IN ✅
```

Toàn bộ quá trình đảm bảo **security**, **validation**, và **persistence** cho phiên đăng nhập! 🎯
