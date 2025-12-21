# Phase 3: Authentication - Testing Guide

## 🚀 Setup hoàn thành

Phase 3 đã được hoàn thành với:
- ✅ Database migrations & seeders (6 tables + sample data)
- ✅ AuthController với login/logout/user endpoints
- ✅ API routes được setup
- ✅ DashboardView (Admin & Employee)
- ✅ Frontend login form

## 📝 Demo Accounts

### Admin
```
Email: admin@staffhub.com
Password: admin123
Role: admin
```

### Employee (John Doe)
```
Email: john@staffhub.com
Password: employee123
Role: employee
Employee Code: EMP-00006
Position: Senior Developer
Department: IT
```

### Other Employees
- an.nguyen@staffhub.com / employee123 (EMP-00001)
- binh.tran@staffhub.com / employee123 (EMP-00002)
- chau.le@staffhub.com / employee123 (EMP-00003)
- dat.pham@staffhub.com / employee123 (EMP-00004)
- hien.hoang@staffhub.com / employee123 (EMP-00005)

## 🔌 API Endpoints

### Authentication Endpoints

#### 1. Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@staffhub.com",
  "password": "admin123"
}
```

**Response (200 OK):**
```json
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "name": "Nguyễn Quản Trị",
    "email": "admin@staffhub.com",
    "role": "admin"
  },
  "message": "Login successfully"
}
```

#### 2. Get Current User
```http
GET /api/user
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "Nguyễn Quản Trị",
  "email": "admin@staffhub.com",
  "role": "admin"
}
```

Nếu là employee có employee info:
```json
{
  "id": 7,
  "name": "John Doe",
  "email": "john@staffhub.com",
  "role": "employee",
  "employee": {
    "id": 6,
    "employee_code": "EMP-00006",
    "position": "Senior Developer",
    "department": "IT",
    "phone": "0901000006",
    "status": "Đang làm việc"
  }
}
```

#### 3. Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Logout successfully"
}
```

#### 4. Register (Optional)
```http
POST /api/register
Content-Type: application/json

{
  "name": "New User",
  "email": "newuser@staffhub.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201 Created):**
```json
{
  "token": "1|xyz...",
  "user": {
    "id": 8,
    "name": "New User",
    "email": "newuser@staffhub.com",
    "role": "employee"
  },
  "message": "User registered successfully"
}
```

## 🧪 Test bằng Postman

### 1. Setup Collection

Tạo Environment trong Postman với các variables:
```
base_url: http://localhost:8000
api_url: {{base_url}}/api
token: (sẽ được fill bởi login request)
```

### 2. Login Request

**Settings:**
- Method: POST
- URL: `{{api_url}}/login`
- Body (raw JSON):
```json
{
  "email": "admin@staffhub.com",
  "password": "admin123"
}
```

**Test Script** (thêm vào Tests tab để auto-save token):
```javascript
if (pm.response.code === 200) {
  var jsonData = pm.response.json();
  pm.environment.set("token", jsonData.token);
  pm.environment.set("user_role", jsonData.user.role);
}
```

### 3. Get User Request

**Settings:**
- Method: GET
- URL: `{{api_url}}/user`
- Headers:
  - Authorization: `Bearer {{token}}`

### 4. Logout Request

**Settings:**
- Method: POST
- URL: `{{api_url}}/logout`
- Headers:
  - Authorization: `Bearer {{token}}`

## 🌐 Test bằng Frontend

### 1. Chạy Frontend Dev Server
```bash
cd frontend
npm run dev
```
Truy cập: http://localhost:5173

### 2. Test Login Flow

1. Truy cập login page
2. Nhập: admin@staffhub.com / admin123
3. Click "Đăng nhập"
4. Nếu thành công:
   - Token được lưu vào localStorage
   - Redirect sang /dashboard
   - Hiển thị Admin Dashboard

### 3. Test Logout

1. Click button "Đăng xuất" ở dashboard
2. Token bị xóa từ localStorage
3. Redirect về /login

## 🔐 Authentication Flow

```
┌─────────────────┐
│  Login Page     │
└────────┬────────┘
         │ Email + Password
         ↓
┌─────────────────────────────────────┐
│  POST /api/login                    │
│  → Validate credentials             │
│  → Create Sanctum token             │
│  → Return token + user info         │
└────────┬────────────────────────────┘
         │ Token
         ↓
┌─────────────────────────────────────┐
│  localStorage.setItem('token')      │
│  localStorage.setItem('user')       │
│  Redirect to Dashboard              │
└────────┬────────────────────────────┘
         │ Token in header
         ↓
┌─────────────────────────────────────┐
│  Protected Routes via Router Guard  │
│  & API calls with token             │
└────────┬────────────────────────────┘
         │ Logout
         ↓
┌─────────────────────────────────────┐
│  POST /api/logout                   │
│  → Delete all tokens                │
│  → Clear localStorage               │
│  → Redirect to /login               │
└─────────────────────────────────────┘
```

## 🚨 Troubleshooting

### Login fails - "Invalid credentials"
- Verify email & password chính xác (case-sensitive)
- Check database có data: `php artisan tinker`
  ```php
  User::all();
  ```

### Token not working - 401 Unauthorized
- Verify token format: `Bearer {token}`
- Check token còn valid (không expire)
- Verify CORS headers

### CORS errors
- Kiểm tra `FRONTEND_URL` trong backend `.env`
- Kiểm tra Sanctum middleware enabled

### Database connection error
- Verify MySQL service running
- Verify credentials trong `.env`
- Verify database `staffhub_db` tồn tại

## 📚 Database Sample Data

### Users Table
| ID | Name | Email | Role | Password Hash |
|---|---|---|---|---|
| 1 | Nguyễn Quản Trị | admin@staffhub.com | admin | ... |
| 2 | Nguyễn Văn An | an.nguyen@staffhub.com | employee | ... |
| 3 | Trần Thị Bình | binh.tran@staffhub.com | employee | ... |
| 4 | Lê Minh Châu | chau.le@staffhub.com | employee | ... |
| 5 | Phạm Quốc Đạt | dat.pham@staffhub.com | employee | ... |
| 6 | Hoàng Thu Hiền | hien.hoang@staffhub.com | employee | ... |
| 7 | John Doe | john@staffhub.com | employee | ... |

### Employees Table Sample
```
EMP-00001: Nguyễn Văn An - Senior Developer - IT - 0901000001 - Đang làm việc
EMP-00002: Trần Thị Bình - HR Manager - Nhân sự - 0901000002 - Đang làm việc
EMP-00003: Lê Minh Châu - Designer - Design - 0901000003 - Đang làm việc
EMP-00004: Phạm Quốc Đạt - Marketing Lead - Marketing - 0901000004 - Đang làm việc
EMP-00005: Hoàng Thu Hiền - Accountant - Kế toán - 0901000005 - Nghỉ việc
EMP-00006: John Doe - Senior Developer - IT - 0901000006 - Đang làm việc
```

### Sample Leave Requests
- EMP-00001: 20-22/12/2025 (Chờ duyệt - Nghỉ phép năm)
- EMP-00003: 15-16/12/2025 (Đã duyệt - Việc gia đình)
- EMP-00004: 10/12/2025 (Từ chối - Khám bệnh)

### Sample Salaries (John Doe)
- Tháng 11/2025: 15M base + 2M bonus = 17M
- Tháng 10/2025: 15M base + 1.5M bonus = 16.5M
- Tháng 9/2025: 15M base + 2.5M bonus = 17.5M

### Sample Notifications
- "Đơn nghỉ phép từ ngày 20/12 đã được phê duyệt" (08/12/2025, đã đọc)
- "Nhắc nhở: Nộp báo cáo tháng 12 trước ngày 15/12" (05/12/2025, đã đọc)
- "Thông báo: Họp team vào thứ 5 tuần này lúc 2PM" (03/12/2025, chưa đọc)

## ✅ Checklist Before Moving to Phase 4

- [ ] Login works with both admin & employee accounts
- [ ] Token được lưu vào localStorage
- [ ] Dashboard hiển thị đúng role (admin/employee)
- [ ] Logout xóa token & redirect về login
- [ ] API endpoints respond correctly
- [ ] No CORS errors
- [ ] Database có đầy đủ sample data

## 🔜 Next: Phase 4 - Dashboard

Khi Phase 3 passed, bắt đầu Phase 4:
- Fetch real data từ API cho dashboard
- Implement statistics (tổng nhân viên, đơn chờ duyệt, etc.)
- Add real notification list
- Admin dashboard: employee list, pending approvals
- Employee dashboard: my leaves, salary info
