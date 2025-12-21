<template>
  <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow border-0" style="width: 420px; border-radius: 16px;">
      <div class="card-body p-5">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center text-white fw-bold mb-3" 
               style="width: 64px; height: 64px; background: #1976D2; border-radius: 12px; font-size: 24px;">
            SH
          </div>
          <h3 class="fw-bold mb-2">Chào mừng đến với StaffHub</h3>
          <p class="text-muted mb-0" style="font-size: 14px;">Đăng nhập để tiếp tục</p>
        </div>

        <form @submit.prevent="handleLogin">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size: 14px;">Email</label>
            <input 
              v-model="credentials.email" 
              type="email" 
              class="form-control" 
              style="background: #F5F5F5; border: 1px solid #E0E0E0; padding: 12px; border-radius: 8px;"
              placeholder="your.email@staffhub.com"
              required
              autocomplete="email"
            />
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size: 14px;">Mật khẩu</label>
            <input 
              v-model="credentials.password" 
              type="password" 
              class="form-control" 
              style="background: #F5F5F5; border: 1px solid #E0E0E0; padding: 12px; border-radius: 8px;"
              placeholder="Nhập mật khẩu"
              required
              autocomplete="current-password"
            />
          </div>

          <div v-if="error" class="alert alert-danger d-flex align-items-center mb-3" role="alert" style="font-size: 14px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>{{ error }}</span>
          </div>

          <button 
            type="submit" 
            class="btn w-100 text-white fw-semibold"
            style="background: #1976D2; padding: 12px; border-radius: 8px; border: none;"
            :disabled="loading"
          >
            <span v-if="!loading">Đăng nhập</span>
            <span v-else class="d-inline-flex align-items-center gap-2">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              Đang xử lý...
            </span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    await authStore.login(credentials.value)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.'
  } finally {
    loading.value = false
  }
}
</script>

