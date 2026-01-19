# 🧾 Luồng hoạt động PROFILE - Frontend → Backend → Database

## 📊 Sơ đồ tổng quát
```
User mở trang /profile → loadProfile()
      ↓
profileService.getProfile() → GET /api/profile (Bearer token)
      ↓
Route /api/profile → ProfileController@show
      ↓
Eloquent load user + employee → trả JSON { data }
      ↓
Frontend đổ form (name, email, phone, position, department)

User bấm “Lưu thay đổi”
      ↓
profileService.updateProfile(payload) → PUT /api/profile
      ↓
ProfileController@update → validate → update users + employees → return JSON { message, data }
      ↓
Frontend hiển thị message, cập nhật state

User đổi mật khẩu
      ↓
profileService.changePassword(payload) → POST /api/profile/change-password
      ↓
ProfileController@changePassword → validate → verify current_password → hash new_password → return message
      ↓
Frontend hiển thị kết quả
```

---

## 🔄 Chi tiết từng luồng

### 1) Lấy hồ sơ (GET /api/profile)
- **UI**: [frontend/src/views/ProfileView.vue](frontend/src/views/ProfileView.vue) → `onMounted(() => loadProfile())`
- **Service**: [frontend/src/services/profileService.js](frontend/src/services/profileService.js)
  ```javascript
  async getProfile() {
    const response = await apiClient.get('/api/profile')
    return response.data.data
  }
  ```
- **Request**: `GET /api/profile` với header `Authorization: Bearer <token>` (gắn bởi axios interceptor)
- **Route**: [backend/routes/api.php](backend/routes/api.php) (trong group `auth:sanctum`)
- **Controller**: [backend/app/Http/Controllers/Api/ProfileController.php](backend/app/Http/Controllers/Api/ProfileController.php)
  ```php
  public function show(Request $request) {
      $user = $request->user()->load('employee');
      return response()->json(['data' => $this->transform($user)]);
  }
  ```
- **DB**: Eloquent lấy user + employee (bảng `users`, `employees`) → không ghi DB.
- **Response sample**:
  ```json
  {
    "data": {
      "id": 1,
      "name": "Nguyễn Quản Trị",
      "email": "admin@staffhub.com",
      "role": "admin",
      "employee": {
        "id": 6,
        "employee_code": "EMP-00006",
        "position": "Senior Developer",
        "department": "IT",
        "phone": "0901000006",
        "status": "Đang làm việc"
      }
    }
  }
  ```

### 2) Cập nhật hồ sơ (PUT /api/profile)
- **UI**: Form “Thông tin cơ bản” → `saveProfile()`
  ```javascript
  const res = await profileService.updateProfile({ ...form })
  success.value = res.message
  profile.value = res.data
  ```
- **Service**: `profileService.updateProfile(payload)` → PUT `/api/profile`
- **Controller**:
  ```php
  $validated = $request->validate([
      'name' => ['required','string','max:255'],
      'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
      'phone' => ['nullable','string','max:50'],
      'position' => ['nullable','string','max:255'],
      'department' => ['nullable','string','max:255'],
  ]);

  $user->update(['name' => $validated['name'], 'email' => $validated['email']]);

  if ($user->employee) {
      $user->employee->update([
          'phone' => $validated['phone'] ?? $user->employee->phone,
          'position' => $validated['position'] ?? $user->employee->position,
          'department' => $validated['department'] ?? $user->employee->department,
      ]);
  } else {
      // nếu chưa có employee record, tạo tối thiểu
      $user->employee()->create([
          'employee_code' => null,
          'position' => $validated['position'] ?? null,
          'department' => $validated['department'] ?? null,
          'phone' => $validated['phone'] ?? null,
          'status' => 'Đang làm việc',
      ]);
  }
  ```
- **DB ghi**: bảng `users` (name, email) và `employees` (phone, position, department). Nếu thiếu employee → tạo bản ghi mới.
- **Response**: `{ message: 'Cập nhật thông tin cá nhân thành công', data: <profile> }`

### 3) Đổi mật khẩu (POST /api/profile/change-password)
- **UI**: Form “Đổi mật khẩu” → `changePass()`
  ```javascript
  await profileService.changePassword({
    current_password,
    new_password,
    new_password_confirmation
  })
  ```
- **Service**: POST `/api/profile/change-password`
- **Controller**:
  ```php
  $validated = $request->validate([
      'current_password' => ['required'],
      'new_password' => ['required','min:6','confirmed'],
  ]);

  if (!Hash::check($validated['current_password'], $user->password)) {
      return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 422);
  }

  $user->update(['password' => bcrypt($validated['new_password'])]);
  return response()->json(['message' => 'Đổi mật khẩu thành công']);
  ```
- **DB ghi**: bảng `users` (password: bcrypt hash)
- **Response**: `{ message: 'Đổi mật khẩu thành công' }`

---

## 🧠 Data & Model liên quan
- Bảng `users`: id, name, email, password, role
- Bảng `employees`: id, user_id, employee_code, position, department, phone, status
- Quan hệ: `User hasOne Employee`
- Middleware: tất cả endpoints profile nằm trong `auth:sanctum` (yêu cầu Bearer token)

---

## 🔐 Validation & Bảo mật
- `email` unique trừ chính user hiện tại (Rule::unique->ignore)
- Password đổi: phải cung cấp `current_password` đúng + xác nhận `new_password` (confirmed)
- Hash mật khẩu: `bcrypt` (Hash::make / bcrypt())
- Yêu cầu token: `auth:sanctum`

---

## ✅ Tóm tắt nhanh
```
GET /api/profile              -> lấy hồ sơ (user + employee)
PUT /api/profile              -> cập nhật name/email + phone/position/department
POST /api/profile/change-password -> đổi mật khẩu (verify current, hash new)
```
Tất cả yêu cầu phải kèm Bearer token, interceptor đã tự gắn từ localStorage.
