<template>
  <AppLayout>
    <h2 class="mb-4">Quản lý đơn nghỉ phép</h2>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="font-weight: 600; color: #666;">Tên nhân viên</th>
              <th style="font-weight: 600; color: #666;">Loại nghỉ</th>
              <th style="font-weight: 600; color: #666;">Ngày bắt đầu</th>
              <th style="font-weight: 600; color: #666;">Ngày kết thúc</th>
              <th style="font-weight: 600; color: #666;">Lý do</th>
              <th style="font-weight: 600; color: #666;">Trạng thái</th>
              <th style="font-weight: 600; color: #666;">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="leaves.length === 0">
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-inbox"></i> Không tìm thấy đơn nghỉ phép
              </td>
            </tr>
            <tr v-for="leave in leaves" :key="leave.id">
              <td class="fw-500">{{ leave.employee_name }}</td>
              <td>{{ leave.type }}</td>
              <td>{{ formatDate(leave.start_date) }}</td>
              <td>{{ formatDate(leave.end_date) }}</td>
              <td>{{ leave.reason }}</td>
              <td>
                <span v-if="leave.status === 'Pending' || leave.status === 'Đang chờ' || leave.status === 'Chờ duyệt'" class="badge" style="background-color: #E3F2FD; color: #1976D2;">
                  <i class="bi bi-clock"></i> Chờ duyệt
                </span>
                <span v-else-if="leave.status === 'Approved' || leave.status === 'Đã duyệt'" class="badge bg-success">
                  <i class="bi bi-check-circle"></i> Đã duyệt
                </span>
                <span v-else-if="leave.status === 'Rejected' || leave.status === 'Từ chối'" class="badge bg-danger">
                  <i class="bi bi-x-circle"></i> Từ chối
                </span>
                <span v-else class="badge bg-secondary">{{ leave.status }}</span>
              </td>
              <td>
                <div v-if="leave.status === 'Pending' || leave.status === 'Đang chờ' || leave.status === 'Chờ duyệt'" class="d-flex gap-2">
                  <button class="btn btn-success btn-sm" @click="approveLeave(leave.id)" :disabled="processing === leave.id">
                    <i class="bi bi-check-lg"></i> Duyệt
                  </button>
                  <button class="btn btn-danger btn-sm" @click="rejectLeave(leave.id)" :disabled="processing === leave.id">
                    <i class="bi bi-x-lg"></i> Từ chối
                  </button>
                </div>
                <span v-else-if="leave.status === 'Approved' || leave.status === 'Đã duyệt'" class="text-muted small">
                  Đã duyệt
                </span>
                <span v-else-if="leave.status === 'Rejected' || leave.status === 'Từ chối'" class="text-muted small">
                  Đã từ chối
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '../components/layouts/AppLayout.vue'
import { leaveRequestsService } from '../services/leaveRequestsService'

const leaves = ref([])
const loading = ref(false)
const processing = ref(null)

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const fetchLeaves = async () => {
  loading.value = true
  try {
    const data = await leaveRequestsService.list({
      page: 1,
      per_page: 50
    })
    leaves.value = data.data || data
  } catch (err) {
    console.error('Failed to load leave requests:', err)
  } finally {
    loading.value = false
  }
}

const approveLeave = async (id) => {
  processing.value = id
  try {
    await leaveRequestsService.approve(id)
    await fetchLeaves()
  } catch (err) {
    console.error('Failed to approve leave:', err)
    alert('Failed to approve leave request')
  } finally {
    processing.value = null
  }
}

const rejectLeave = async (id) => {
  processing.value = id
  try {
    await leaveRequestsService.reject(id)
    await fetchLeaves()
  } catch (err) {
    console.error('Failed to reject leave:', err)
    alert('Failed to reject leave request')
  } finally {
    processing.value = null
  }
}

onMounted(() => {
  fetchLeaves()
})
</script>

<style scoped>
.table thead th {
  border-bottom: 1px solid #E0E0E0 !important;
}

.table tbody td {
  border-bottom: 1px solid #F0F0F0;
}

.badge {
  font-weight: 500;
  font-size: 12px;
  padding: 4px 10px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-sm {
  font-size: 13px;
  padding: 5px 12px;
}
</style>
