# StaffHub Frontend

Vue.js 3 frontend cho hệ thống quản lý nhân viên StaffHub.

## 🚀 Quick Start

```bash
# Cài đặt dependencies
npm install

# Chạy dev server
npm run dev

# Build production
npm run build

# Preview production build
npm run preview
```

## 📦 Dependencies

- **vue**: ^3.5.24 - Vue.js framework
- **vue-router**: ^4.x - Routing
- **pinia**: State management
- **axios**: HTTP client
- **bootstrap**: ^5.x - UI framework
- **bootstrap-icons**: Icons

## 🔧 Configuration

Cấu hình trong file `.env`:
```env
VITE_API_URL=http://localhost:8000
VITE_APP_NAME=StaffHub
```

## 📁 Project Structure

```
src/
├── assets/              # Static assets (images, fonts, etc.)
├── components/          # Reusable Vue components
│   └── layouts/         # Layout components
├── router/              # Vue Router configuration
│   └── index.js         # Routes & navigation guards
├── services/            # API service layer
│   ├── api.js           # Axios instance & interceptors
│   └── authService.js   # Authentication API calls
├── stores/              # Pinia stores
│   └── auth.js          # Authentication state
├── views/               # Page components
│   ├── LoginView.vue
│   ├── DashboardView.vue
│   └── ...
├── App.vue              # Root component
└── main.js              # Application entry point
```

## 🔐 Authentication

Authentication được quản lý bởi Pinia store (`stores/auth.js`) và sử dụng Laravel Sanctum tokens.

### Login Flow
1. User nhập credentials
2. Call `/sanctum/csrf-cookie` để lấy CSRF token
3. Call `/api/login` với credentials
4. Store token và user info vào localStorage
5. Redirect to dashboard

### Protected Routes
Routes được protect bởi navigation guards trong `router/index.js`.

## 🎨 UI Components

Sử dụng Bootstrap 5 cho UI components. Import Bootstrap CSS và JS trong `main.js`.

## 📝 Development Guidelines

- Sử dụng Composition API với `<script setup>`
- Quản lý state với Pinia stores
- API calls qua services layer
- Component naming: PascalCase
- File naming: kebab-case

## 🔗 API Integration

Tất cả API calls đi qua `services/api.js` - Axios instance đã được cấu hình với:
- Base URL từ environment variable
- Credentials support
- Token interceptor
- Error handling

## 🚧 Development Status

✅ Phase 1 Complete:
- Project setup
- Router configuration
- Authentication flow
- Basic views structure
- Bootstrap integration

🔜 Next Phases:
- Phase 2: Database & Models
- Phase 3: Complete Authentication
- Phase 4+: Feature implementation
