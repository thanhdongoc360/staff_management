<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Đơn nghỉ phép của tôi</h2>
      <button class="btn btn-primary" @click="showForm = !showForm">
        <i class="bi bi-plus"></i> Tạo đơn nghỉ phép
      </button>
    </div>

    <!-- Create Form Card -->
    <div v-if="showForm" class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title mb-4">Tạo đơn nghỉ phép</h5>
        <form @submit.prevent="submit">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Ngày bắt đầu *</label>
              <input type="date" v-model="form.start_date" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Ngày kết thúc *</label>
              <input type="date" v-model="form.end_date" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Loại nghỉ *</label>
              <select v-model="form.type" class="form-select" required>
                <option disabled value="">Chọn loại nghỉ</option>
                <option v-for="t in leaveTypes" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label">Lý do *</label>
              <textarea v-model="form.reason" rows="3" class="form-control" required placeholder="Mô tả ngắn gọn"></textarea>
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                Gửi đơn
              </button>
              <button class="btn btn-secondary ms-2" type="button" @click="resetForm">Hủy</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="font-weight: 600; color: #666;">Ngày bắt đầu</th>
              <th style="font-weight: 600; color: #666;">Ngày kết thúc</th>
              <th style="font-weight: 600; color: #666;">Số ngày</th>
              <th style="font-weight: 600; color: #666;">Loại nghỉ</th>
              <th style="font-weight: 600; color: #666;">Lý do</th>
              <th style="font-weight: 600; color: #666;">Trạng thái</th>
              <th style="font-weight: 600; color: #666;">Ngày nộp</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="!leaves.length">
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-inbox"></i> Chưa có đơn nghỉ phép
              </td>
            </tr>
            <tr v-for="item in leaves" :key="item.id">
              <td>{{ formatDate(item.start_date) }}</td>
              <td>{{ formatDate(item.end_date) }}</td>
              <td>{{ item.days || '-' }}</td>
              <td>{{ item.type }}</td>
              <td>{{ item.reason }}</td>
              <td>
                <span v-if="item.status === 'Pending' || item.status === 'Đang chờ' || item.status === 'Chờ duyệt'" class="badge" style="background-color: #E3F2FD; color: #1976D2;">
                  <i class="bi bi-clock"></i> Chờ duyệt
                </span>
                <span v-else-if="item.status === 'Approved' || item.status === 'Đã duyệt'" class="badge bg-success">
                  <i class="bi bi-check-circle"></i> Đã duyệt
                </span>
                <span v-else-if="item.status === 'Rejected' || item.status === 'Từ chối'" class="badge bg-danger">
                  <i class="bi bi-x-circle"></i> Từ chối
                </span>
                <span v-else class="badge bg-secondary">{{ item.status }}</span>
              </td>
              <td>{{ formatDateTime(item.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import AppLayout from '../components/layouts/AppLayout.vue'
import { leaveRequestsService } from '../services/leaveRequestsService'

const leaves = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const loading = ref(false)
const submitting = ref(false)
const showForm = ref(false)

const leaveTypes = ['Vacation', 'Sick Leave', 'Personal', 'Other']
const form = ref({ start_date: '', end_date: '', reason: '', type: '' })

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const statusClass = (status) => {
  if (status === 'Đã duyệt' || status === 'Approved') return 'badge--success'
  if (status === 'Từ chối' || status === 'Rejected') return 'badge--danger'
  return 'badge--warning'
}

const loadLeaves = async (page = 1) => {
  loading.value = true
  try {
    const data = await leaveRequestsService.myList({ page, per_page: meta.value.per_page })
    leaves.value = data.data
    meta.value = data.meta
  } catch (err) {
    console.error(err)
    alert('Failed to load leave requests')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = { start_date: '', end_date: '', reason: '', type: '' }
  showForm.value = false
}

const submit = async () => {
  submitting.value = true
  try {
    await leaveRequestsService.create({ ...form.value })
    resetForm()
    await loadLeaves()
    alert('Leave request submitted successfully!')
  } catch (err) {
    console.error(err)
    alert('Failed to submit leave request')
  } finally {
    submitting.value = false
  }
}

onMounted(() => loadLeaves())
</script>

<style scoped>
.card-title {
  font-weight: 600;
  color: #333;
}

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
</style>
