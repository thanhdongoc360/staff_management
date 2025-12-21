<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Hồ sơ cá nhân</h2>
      <div class="d-flex gap-2">
        <span class="badge bg-primary">{{ profile.role }}</span>
        <span v-if="profile.employee?.employee_code" class="badge bg-secondary">{{ profile.employee.employee_code }}</span>
      </div>
    </div>

    <!-- Basic Information Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title mb-4">Thông tin cơ bản</h5>
        <form @submit.prevent="saveProfile">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ và tên *</label>
              <input v-model="form.name" type="text" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input v-model="form.email" type="email" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Số điện thoại</label>
              <input v-model="form.phone" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Vị trí</label>
              <input v-model="form.position" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Phòng ban</label>
              <input v-model="form.department" type="text" class="form-control" />
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                Lưu thay đổi
              </button>
              <span v-if="success" class="text-success ms-3">{{ success }}</span>
              <span v-if="error" class="text-danger ms-3">{{ error }}</span>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password Card -->
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Đổi mật khẩu</h5>
        <form @submit.prevent="changePass">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Mật khẩu hiện tại *</label>
              <input v-model="passwordForm.current_password" type="password" class="form-control" required />
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-6">
              <label class="form-label">Mật khẩu mới *</label>
              <input v-model="passwordForm.new_password" type="password" class="form-control" required minlength="6" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Xác nhận mật khẩu mới *</label>
              <input v-model="passwordForm.new_password_confirmation" type="password" class="form-control" required minlength="6" />
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit" :disabled="changing">
                <span v-if="changing" class="spinner-border spinner-border-sm me-2"></span>
                Đổi mật khẩu
              </button>
              <span v-if="passSuccess" class="text-success ms-3">{{ passSuccess }}</span>
              <span v-if="passError" class="text-danger ms-3">{{ passError }}</span>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import AppLayout from '../components/layouts/AppLayout.vue'
import { profileService } from '../services/profileService'

const profile = ref({ employee: {} })
const form = reactive({ name: '', email: '', phone: '', position: '', department: '' })
const passwordForm = reactive({ current_password: '', new_password: '', new_password_confirmation: '' })

const saving = ref(false)
const changing = ref(false)
const success = ref('')
const error = ref('')
const passSuccess = ref('')
const passError = ref('')

const loadProfile = async () => {
  try {
    const data = await profileService.getProfile()
    profile.value = data
    form.name = data.name || ''
    form.email = data.email || ''
    form.phone = data.employee?.phone || ''
    form.position = data.employee?.position || ''
    form.department = data.employee?.department || ''
  } catch (err) {
    console.error(err)
    error.value = 'Không tải được hồ sơ'
  }
}

const saveProfile = async () => {
  success.value = ''
  error.value = ''
  saving.value = true
  try {
    const res = await profileService.updateProfile({ ...form })
    success.value = res.message || 'Đã lưu'
    profile.value = res.data
  } catch (err) {
    console.error(err)
    error.value = err.response?.data?.message || 'Không thể lưu thông tin'
  } finally {
    saving.value = false
  }
}

const changePass = async () => {
  passSuccess.value = ''
  passError.value = ''
  changing.value = true
  try {
    const res = await profileService.changePassword({ ...passwordForm })
    passSuccess.value = res.message || 'Đã đổi mật khẩu'
    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirmation = ''
  } catch (err) {
    console.error(err)
    passError.value = err.response?.data?.message || 'Không thể đổi mật khẩu'
  } finally {
    changing.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.card-title {
  font-weight: 600;
  color: #333;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

h2 {
  margin: 4px 0;
}

.muted { color: #6b7280; }

.chips { display: flex; gap: 8px; flex-wrap: wrap; }
.chip {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 600;
}

.card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
}

.form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

label {
  display: flex;
  flex-direction: column;
  font-weight: 600;
  gap: 6px;
  color: #111827;
}

input {
  padding: 8px 10px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
}

.form__actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.primary {
  background: #111827;
  color: #fff;
  border: 1px solid #111827;
  padding: 10px 12px;
  border-radius: 10px;
  cursor: pointer;
}

.text-success { color: #16a34a; }
.text-danger { color: #dc2626; }
</style>
