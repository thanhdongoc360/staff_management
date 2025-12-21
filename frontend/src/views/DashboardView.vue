<template>
  <AppLayout>
    <!-- Admin Dashboard -->
    <div v-if="authStore.isAdmin">
      <h2 class="mb-4">Bảng điều khiển</h2>

      <!-- Stats Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
              <div class="flex-shrink-0 me-3">
                <div class="d-flex align-items-center justify-content-center" 
                     style="width: 56px; height: 56px; background: #E3F2FD; border-radius: 12px;">
                  <i class="bi bi-people fs-3 text-primary"></i>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="text-muted mb-1" style="font-size: 14px;">Tổng nhân viên</h6>
                <h3 class="mb-0 fw-bold">{{ stats.totalEmployees }}</h3>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
              <div class="flex-shrink-0 me-3">
                <div class="d-flex align-items-center justify-content-center" 
                     style="width: 56px; height: 56px; background: #FFF3E0; border-radius: 12px;">
                  <i class="bi bi-file-earmark-text fs-3 text-warning"></i>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="text-muted mb-1" style="font-size: 14px;">Đơn nghỉ chờ duyệt</h6>
                <h3 class="mb-0 fw-bold">{{ stats.pendingLeaves }}</h3>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
              <div class="flex-shrink-0 me-3">
                <div class="d-flex align-items-center justify-content-center" 
                     style="width: 56px; height: 56px; background: #E8F5E9; border-radius: 12px;">
                  <i class="bi bi-briefcase fs-3 text-success"></i>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="text-muted mb-1" style="font-size: 14px;">Dự án đang hoạt động</h6>
                <h3 class="mb-0 fw-bold">{{ stats.activeProjects }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Employees -->
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Nhân viên gần đây</h5>
            <button class="btn btn-primary btn-sm" @click="viewAllEmployees">
              Xem tất cả nhân viên
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Họ và tên</th>
                  <th>Vị trí</th>
                  <th>Phòng ban</th>
                  <th>Email</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="employee in recentEmployees" :key="employee.id">
                  <td class="fw-semibold">{{ employee.name }}</td>
                  <td>{{ employee.position }}</td>
                  <td>{{ employee.department }}</td>
                  <td>{{ employee.email }}</td>
                  <td>
                    <span class="badge bg-success-subtle text-success" style="padding: 6px 12px;">
                      {{ employee.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Employee Dashboard -->
    <div v-else>
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Bảng điều khiển</h2>
        <button class="btn btn-outline-primary" @click="router.push('/profile')">
          <i class="bi bi-person"></i> Xem hồ sơ
        </button>
      </div>

      <div class="row g-4 mb-4">
        <!-- Personal Information Card -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-4">Thông tin cá nhân</h5>
              
              <div class="mb-3">
                <label class="text-muted small mb-1">Họ tên</label>
                <div class="fw-semibold">{{ employeeInfo.name }}</div>
              </div>
              
              <div class="mb-3">
                <label class="text-muted small mb-1">Email</label>
                <div class="fw-semibold">{{ employeeInfo.email }}</div>
              </div>
              
              <div class="mb-3">
                <label class="text-muted small mb-1">Vị trí</label>
                <div class="fw-semibold">{{ employeeInfo.position }}</div>
              </div>
              
              <div class="mb-3">
                <label class="text-muted small mb-1">Phòng ban</label>
                <div class="fw-semibold">{{ employeeInfo.department }}</div>
              </div>
              
              <div>
                <label class="text-muted small mb-1">Trạng thái</label>
                <div>
                  <span class="badge bg-success">{{ employeeInfo.status }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Work Schedule Card -->
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title mb-0">Lịch làm việc</h5>
                <i class="bi bi-calendar3 text-muted"></i>
              </div>

              <div class="schedule-list">
                <div v-for="schedule in workSchedule" :key="schedule.day" class="schedule-item mb-3 pb-3 border-bottom">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-semibold">{{ schedule.day }}</div>
                      <div class="text-muted small">{{ schedule.time }}</div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">{{ schedule.project }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Leave Request History -->
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">Lịch sử đơn nghỉ phép</h5>
            <button class="btn btn-primary" @click="router.push('/my-leaves')">
              <i class="bi bi-plus"></i> Nộp đơn nghỉ phép
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Loại nghỉ</th>
                  <th>Ngày bắt đầu</th>
                  <th>Ngày kết thúc</th>
                  <th>Lý do</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="leaveHistory.length === 0">
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Chưa có đơn nghỉ phép
                  </td>
                </tr>
                <tr v-for="leave in leaveHistory" :key="leave.id">
                  <td class="fw-semibold">{{ leave.type }}</td>
                  <td>{{ formatDate(leave.start_date) }}</td>
                  <td>{{ formatDate(leave.end_date) }}</td>
                  <td>{{ leave.reason }}</td>
                  <td>
                    <span :class="['badge', getStatusClass(leave.status)]">
                      {{ leave.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AppLayout from '../components/layouts/AppLayout.vue'
import employeesService from '../services/employeesService'
import { leaveRequestsService } from '../services/leaveRequestsService'
import { profileService } from '../services/profileService'

const router = useRouter()
const authStore = useAuthStore()

const stats = ref({
  totalEmployees: 0,
  pendingLeaves: 0,
  activeProjects: 12
})

const recentEmployees = ref([])
const loading = ref(false)

// Employee dashboard data
const employeeInfo = ref({
  name: '',
  email: '',
  position: '',
  department: '',
  status: 'Active'
})

const workSchedule = ref([
  { day: 'Monday', time: '9:00 AM - 5:00 PM', project: 'Project Alpha' },
  { day: 'Tuesday', time: '9:00 AM - 5:00 PM', project: 'Project Beta' },
  { day: 'Wednesday', time: '9:00 AM - 5:00 PM', project: 'Project Alpha' },
  { day: 'Thursday', time: '9:00 AM - 5:00 PM', project: 'Project Beta' },
  { day: 'Friday', time: '9:00 AM - 5:00 PM', project: 'Project Gamma' }
])

const leaveHistory = ref([])

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const getStatusClass = (status) => {
  if (status === 'Approved' || status === 'Đã duyệt') return 'bg-success'
  if (status === 'Rejected' || status === 'Từ chối') return 'bg-danger'
  return 'bg-primary-subtle text-primary'
}

const loadAdminDashboard = async () => {
  try {
    // Load employees
    const employeesData = await employeesService.getEmployees({ page: 1, per_page: 5 })
    recentEmployees.value = employeesData.data
    stats.value.totalEmployees = employeesData.meta.total

    // Load pending leave requests
    const leavesData = await leaveRequestsService.list({ status: 'Đang chờ', page: 1, per_page: 1 })
    stats.value.pendingLeaves = leavesData.meta.total
  } catch (error) {
    console.error('Failed to load admin dashboard:', error)
  }
}

const loadEmployeeDashboard = async () => {
  try {
    // Load employee profile
    const profile = await profileService.getProfile()
    employeeInfo.value = {
      name: profile.name || '',
      email: profile.email || '',
      position: profile.employee?.position || '',
      department: profile.employee?.department || '',
      status: profile.employee?.status || 'Active'
    }

    // Load leave history
    const leavesData = await leaveRequestsService.myList({ page: 1, per_page: 5 })
    leaveHistory.value = leavesData.data || []
  } catch (error) {
    console.error('Failed to load employee dashboard:', error)
  }
}

const loadDashboardData = async () => {
  loading.value = true
  try {
    if (authStore.isAdmin) {
      await loadAdminDashboard()
    } else {
      await loadEmployeeDashboard()
    }
  } finally {
    loading.value = false
  }
}

const viewAllEmployees = () => {
  router.push('/employees')
}

onMounted(() => {
  loadDashboardData()
})
</script>

<style scoped>
.badge {
  font-weight: 500;
  font-size: 13px;
}

.schedule-item:last-child {
  border-bottom: none !important;
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}

.card-title {
  font-weight: 600;
  color: #333;
}
</style>
