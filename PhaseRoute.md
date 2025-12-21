# Lộ trình xây dựng Project StaffHub - Quản lý nhân viên

Dựa trên các giao diện bạn cung cấp, đây là lộ trình chi tiết để xây dựng hệ thống:

## **Phase 1: Setup & Cấu trúc dự án (1-2 ngày)**

### 1.1 Backend Setup
- Cài đặt Laravel 9
- Cấu hình database MySQL
- Setup authentication với Laravel Sanctum/Passport
- Cấu hình CORS cho API

### 1.2 Frontend Setup  
- Cài đặt Vue.js 3 (với Vite)
- Setup Vue Router
- Cài đặt Bootstrap 5
- Setup Axios cho API calls
- Cấu hình state management (Pinia/Vuex)

## **Phase 2: Database & Models (2-3 ngày)**

### 2.1 Thiết kế database
- **users**: id, name, email, password, role (admin/staff)
- **employees**: id, user_id, employee_code, position, department, phone, status
- **leave_requests**: id, employee_id, start_date, end_date, reason, status, type
- **salaries**: id, employee_id, base_salary, bonus, total, month, year
- **notifications**: id, user_id, title, content, date, is_read
- **schedules**: id, title, date, time, description

### 2.2 Tạo Models & Migrations
- Tạo models với relationships
- Seeders cho dữ liệu mẫu

## **Phase 3: Authentication (2-3 ngày)** ✅ COMPLETED

- ✅ **Login page** (màn 1) - LoginView.vue
- ✅ API login/logout - AuthController.php + routes/api.php
- ✅ Middleware phân quyền (admin/staff) - CheckRole.php
- ✅ Protected routes - router guards + auth:sanctum
- ✅ Token management - Pinia store + localStorage + Axios interceptor

## **Phase 4: Dashboard (2 ngày)** ✅ COMPLETED

### Admin Dashboard (màn 2) ✅
- ✅ Thống kê tổng nhân viên
- ✅ Đơn nghỉ phép chờ duyệt  
- ✅ Nhân viên đang làm việc
- ✅ Danh sách nhân viên gần đây
- ✅ API endpoints (AdminDashboardController)

### Employee Dashboard (màn 5) ✅
- ✅ Đơn chờ duyệt/đã duyệt (stats)
- ✅ Thông báo mới
- ✅ Thông tin nhân viên
- ✅ Thông báo gần đây
- ✅ API endpoints (EmployeeDashboardController)

### Supporting Features ✅
- ✅ NotificationController (fetch, mark as read)
- ✅ ScheduleController (CRUD, upcoming)
- ✅ Dashboard services (dashboardService.js)
- ✅ Real data fetching from API
- ✅ Role-based layouts (Admin vs Employee)

## **Phase 5: Quản lý Nhân viên (3-4 ngày)** ✅ COMPLETED

### Màn 3: Danh sách nhân viên ✅
- ✅ Hiển thị table với phân trang
- ✅ Filter theo trạng thái
- ✅ Actions: Xem, Sửa, Xóa (edit/delete)

### CRUD Operations ✅
- ✅ API: GET, POST, PUT, DELETE employees (EmployeeController, routes/api.php)
- ✅ Form thêm/sửa nhân viên (modal EmployeesView)
- ✅ Validation (Laravel validate + required fields)

## **Phase 6: Quản lý Đơn từ (3-4 ngày)** ✅ COMPLETED

### Màn 4: Danh sách đơn từ (Admin)
- ✅ Hiển thị tất cả đơn từ (bảng + phân trang)
- ✅ Filter theo trạng thái, loại đơn
- ✅ Actions: Duyệt/Từ chối, Xóa

### Màn 7: Đơn nghỉ phép của tôi (Staff)
- ✅ Danh sách đơn của nhân viên
- ✅ Nộp đơn mới (form tạo)
- ✅ Xem trạng thái đơn

### Features
- ✅ API CRUD leave requests (admin CRUD, employee create/list)
- ✅ Approval workflow (duyệt/từ chối, cập nhật trạng thái)
- ✅ Email notifications + in-app notifications (gửi admin khi có đơn mới, gửi nhân viên khi trạng thái đổi)

## **Phase 7: Thông tin cá nhân (1-2 ngày)** ✅ COMPLETED

### Màn 6: Profile
- ✅ Hiển thị thông tin nhân viên (user + employee)
- ✅ Chỉnh sửa thông tin cá nhân (name, email, phone, vị trí, phòng ban)
- ✅ Đổi mật khẩu (verify current, confirm new)

## **Phase 8: Bảng lương (2-3 ngày)** ✅ COMPLETED

### Màn 8: Salary Management
- ✅ Xem lương theo tháng/năm (admin + staff)
- ✅ Tính toán: Lương cơ bản + Thưởng = Tổng
- ✅ Admin: Quản lý lương tất cả nhân viên (tạo/sửa/xóa)
- ✅ Staff: Chỉ xem lương của mình
- Export PDF/Excel (optional) — chưa triển khai (optional)

## **Phase 9: Thông báo (2 ngày)** ✅ COMPLETED

### Màn 9: Notifications
- ✅ Hiển thị danh sách thông báo (phân trang)
- ✅ Đánh dấu đã đọc / đánh dấu tất cả
- Badge số lượng thông báo mới
- Real-time (Pusher/Echo) — optional, chưa triển khai

## **Phase 10: Testing & Polish (2-3 ngày)**

### 10.1 Backend Tests
- ✅ Unit/feature tests cho API: Auth, Employees CRUD, Leave Requests (create/list/approve/reject), Salaries (CRUD + my-salaries), Notifications (list/pagination/unread/mark read)

### 10.2 Integration & Data
- Integration tests cho các luồng chính: employee đăng nhập → nộp đơn nghỉ → admin duyệt → notification/email
- Seeders/mocks phục vụ test (users admin/employee mẫu, employees, salaries mẫu)

### 10.3 UI/UX Polish
- Thống nhất button/badge/toast, trạng thái loading/empty/error trên các page
- Cải thiện responsive cho bảng (stacked cards/mobile scroll), form spacing

### 10.4 Performance & Reliability
- Kiểm tra lỗi biên (validation, 401/403/404), fallback email/log khi không có SMTP
- Optional: debounce search/filter, cache nhẹ dashboard stats

## **Phase 11: Deployment (1-2 ngày)**

- Setup production environment
- Database migration
- Build frontend
- Deploy backend & frontend
- SSL certificate
- Backup strategy

---

## **Tổng thời gian ước tính: 3-4 tuần**

## **Tech Stack chi tiết:**
- **Backend**: Laravel 9 + Sanctum + MySQL
- **Frontend**: Vue 3 + Vue Router + Pinia + Axios
- **UI**: Bootstrap 5 + Bootstrap Icons
- **Dev Tools**: Vite, Composer, NPM

Bạn có muốn tôi bắt đầu setup project với Phase 1 không? Tôi sẽ tạo:
1. Cấu trúc thư mục Laravel backend
2. Cấu trúc Vue.js frontend  
3. File cấu hình cần thiết
4. Database schema ban đầu

