<template>
  <div class="app-layout">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(90deg, #1976D2 0%, #42A5F5 100%);">
      <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center text-white" href="#">
          <div class="d-flex align-items-center justify-content-center text-white fw-bold me-2" 
               style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 8px;">
            SH
          </div>
          <span class="fw-bold">StaffHub</span>
        </a>
        
        <div class="ms-auto d-flex align-items-center gap-3">
          <div class="text-white d-flex align-items-center">
            <i class="bi bi-person-circle me-2"></i>
            <span>{{ user?.name || 'User' }}</span>
          </div>
          <button class="btn btn-outline-light btn-sm" @click="handleLogout">
            <i class="bi bi-box-arrow-right me-1"></i>
            Đăng xuất
          </button>
        </div>
      </div>
    </nav>

    <!-- Main Content with Sidebar -->
    <div class="d-flex">
      <!-- Sidebar -->
      <div class="sidebar bg-white border-end" style="width: 220px; min-height: calc(100vh - 56px);">
        <nav class="nav flex-column p-3">
          <router-link 
            to="/dashboard" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/dashboard' }"
          >
            <i class="bi bi-grid-1x2 me-2"></i>
            Bảng điều khiển
          </router-link>
          
          <router-link 
            v-if="isAdmin"
            to="/employees" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/employees' }"
          >
            <i class="bi bi-people me-2"></i>
            Nhân viên
          </router-link>
          
          <router-link 
            v-if="isAdmin"
            to="/leave-requests" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/leave-requests' }"
          >
            <i class="bi bi-file-earmark-text me-2"></i>
            Đơn nghỉ phép
          </router-link>
          
          <router-link 
            v-if="!isAdmin"
            to="/my-leaves" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/my-leaves' }"
          >
            <i class="bi bi-calendar-check me-2"></i>
            Nghỉ phép của tôi
          </router-link>
          
          <router-link 
            to="/salary" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/salary' }"
          >
            <i class="bi bi-cash-coin me-2"></i>
            Lương
          </router-link>
          
          <router-link 
            to="/profile" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/profile' }"
          >
            <i class="bi bi-person me-2"></i>
            Hồ sơ
          </router-link>
          
          <router-link 
            to="/notifications" 
            class="nav-link d-flex align-items-center"
            :class="{ active: $route.path === '/notifications' }"
          >
            <i class="bi bi-bell me-2"></i>
            Thông báo
          </router-link>
        </nav>
      </div>

      <!-- Page Content -->
      <div class="flex-grow-1" style="background: #F5F5F5; min-height: calc(100vh - 56px);">
        <div class="container-fluid p-4">
          <slot></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)
const isAdmin = computed(() => authStore.isAdmin)

const handleLogout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
    router.push('/login')
  }
}
</script>

<style scoped>
.nav-link {
  color: #666;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s;
}

.nav-link:hover {
  background: #F0F0F0;
  color: #1976D2;
}

.nav-link.active {
  background: #1976D2;
  color: white;
  font-weight: 600;
}

.nav-link i {
  font-size: 18px;
}
</style>
