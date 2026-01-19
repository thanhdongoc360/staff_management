# 🚪 Luồng Hoạt Động LOGOUT - Frontend → Backend → Database

## 📊 Sơ đồ tổng quan
```
User click "Đăng xuất"
      ↓
UI Button (AppLayout/LoginView) → authStore.logout()
      ↓
authService.logout() → POST /api/logout (Bearer <token>)
      ↓
Route /api/logout → AuthController@logout
      ↓
Sanctum: lấy user từ token → xóa tokens trong personal_access_tokens
      ↓
Backend trả JSON { message }
      ↓
Frontend: clear Pinia state + localStorage
      ↓
Redirect /login (hoặc reload)
```
    
---

## 🔄 Luồng chi tiết 10 bước (có mã nguồn)

### 1) User nhấn nút Đăng xuất
- Vị trí: nút logout ở layout / header.
- Gọi hàm trên component: `authStore.logout()`.

### 2) Store (Pinia) thực thi logout
**File:** `frontend/src/stores/auth.js`
```javascript
// actions
async logout() {
  try {
    await authService.logout();      // ⬅️ gọi service gửi request
  } finally {
    this.token = null;               // xoá state
    this.user = null;
    this.isAuthenticated = false;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  }
}
```

### 3) Service gửi HTTP request
**File:** `frontend/src/services/authService.js`
```javascript
async logout() {
  const response = await apiClient.post('/api/logout'); // POST kèm Bearer token
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  return response.data; // { message: 'Logout successfully' }
}
```

### 4) axios interceptor tự gắn token
**File:** `frontend/src/services/api.js`
```javascript
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`; // 👈 gắn Bearer
  return config;
});
```

### 5) HTTP Request gửi đi
```
POST http://localhost:8000/api/logout
Headers:
  Authorization: Bearer 1|xyz123abc...
  Content-Type: application/json
Body: {} (trống)
```

### 6) Backend Route match
**File:** `backend/routes/api.php`
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); // ⬅️ match POST /api/logout
});
```

### 7) Middleware Sanctum xác thực token
- Đọc header Authorization → tách token.
- Hash token → tìm trong bảng `personal_access_tokens`.
- Nạp user tương ứng vào `$request->user()`.

### 8) Controller xóa token
**File:** `backend/app/Http/Controllers/Api/AuthController.php`
```php
public function logout(Request $request)
{
    $request->user()->tokens()->delete(); // xoá tất cả token của user

    return response()->json([
        'message' => 'Logout successfully',
    ], 200);
}
```

### 9) Database thực thi DELETE
- Bảng: `personal_access_tokens`
- SQL tương đương:
```sql
DELETE FROM personal_access_tokens
WHERE tokenable_id = <user_id>
  AND tokenable_type = 'App\\Models\\User';
```
- Kết quả: mọi token của user hết hiệu lực ngay.

### 10) Frontend dọn dẹp & điều hướng
- Pinia state: `token = null`, `user = null`, `isAuthenticated = false`.
- localStorage: xoá `token`, `user`.
- Router: `router.push('/login')` (hoặc reload tuỳ UX).

---

## 📜 Đoạn mã đầy đủ (tham chiếu nhanh)

**frontend/src/services/authService.js**
```javascript
async logout() {
  const response = await apiClient.post('/api/logout');
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  return response.data;
}
```

**frontend/src/stores/auth.js**
```javascript
async logout() {
  try {
    await authService.logout();
  } finally {
    this.token = null;
    this.user = null;
    this.isAuthenticated = false;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  }
}
```

**backend/routes/api.php**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

**backend/app/Http/Controllers/Api/AuthController.php**
```php
public function logout(Request $request)
{
    $request->user()->tokens()->delete();

    return response()->json([
        'message' => 'Logout successfully',
    ], 200);
}
```

---

## ✅ Tóm tắt luồng
1) Frontend gửi `POST /api/logout` kèm Bearer token.
2) Sanctum xác thực token, tìm user.
3) Controller xoá token trong `personal_access_tokens`.
4) Backend trả JSON `{ message }`.
5) Frontend xoá token + user trong state và localStorage.
6) Điều hướng về `/login` → phiên đăng nhập kết thúc.
