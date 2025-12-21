<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Quản lý lương</h2>
      <button v-if="isAdmin" class="btn btn-primary" @click="openCreate">
        <i class="bi bi-plus"></i> Thêm lương
      </button>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Tháng</label>
            <select v-model.number="filters.month" @change="loadData()" class="form-select">
              <option :value="''">Tất cả</option>
              <option v-for="m in 12" :key="m" :value="m">Tháng {{ m }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Năm</label>
            <input type="number" v-model.number="filters.year" min="2000" max="2100" @change="loadData()" class="form-control" />
          </div>
          <div class="col-md-6" v-if="isAdmin">
            <label class="form-label">Nhân viên</label>
            <select v-model="filters.employee_id" @change="loadData()" class="form-select">
              <option value="">Tất cả nhân viên</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="font-weight: 600; color: #666;">Tên nhân viên</th>
              <th style="font-weight: 600; color: #666;">Tháng/Năm</th>
              <th style="font-weight: 600; color: #666;">Lương cơ bản</th>
              <th style="font-weight: 600; color: #666;">Thưởng</th>
              <th style="font-weight: 600; color: #666;">Tổng</th>
              <th style="font-weight: 600; color: #666;">Ghi chú</th>
              <th style="font-weight: 600; color: #666;" v-if="isAdmin">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td :colspan="isAdmin ? 7 : 6" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="rows.length === 0">
              <td :colspan="isAdmin ? 7 : 6" class="text-center py-4 text-muted">
                <i class="bi bi-inbox"></i> Không tìm thấy bảng lương
              </td>
            </tr>
            <tr v-for="row in rows" :key="row.id">
              <td>
                <div class="fw-500">{{ row.employee_name || 'N/A' }}</div>
                <small class="text-muted">{{ row.employee_code || '' }}</small>
              </td>
              <td>Month {{ row.month }}/{{ row.year }}</td>
              <td>{{ formatMoney(row.base_salary) }}</td>
              <td>{{ formatMoney(row.bonus) }}</td>
              <td class="fw-bold">{{ formatMoney(row.total) }}</td>
              <td>{{ row.note || '-' }}</td>
              <td v-if="isAdmin">
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-link text-primary p-0" @click="openEdit(row)" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-link text-danger p-0" @click="remove(row.id)" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-backdrop d-flex">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingId ? 'Chỉnh sửa lương' : 'Thêm lương' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label">Nhân viên *</label>
                <select v-model.number="form.employee_id" :disabled="!!editingId" class="form-select">
                  <option disabled value="">Chọn nhân viên</option>
                  <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tháng *</label>
                <select v-model.number="form.month" class="form-select">
                  <option v-for="m in 12" :key="m" :value="m">Tháng {{ m }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Năm *</label>
                <input type="number" v-model.number="form.year" min="2000" max="2100" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Lương cơ bản *</label>
                <input type="number" v-model.number="form.base_salary" min="0" step="1000" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Thưởng</label>
                <input type="number" v-model.number="form.bonus" min="0" step="1000" class="form-control" />
              </div>
              <div class="col-md-12">
                <label class="form-label">Ghi chú</label>
                <input type="text" v-model="form.note" class="form-control" />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeModal">Đóng</button>
            <button class="btn btn-primary" :disabled="saving" @click="save">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              Lưu
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import AppLayout from '../components/layouts/AppLayout.vue'
import { salaryService } from '../services/salaryService'
import employeesService from '../services/employeesService'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.isAdmin)

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const filters = reactive({ month: '', year: new Date().getFullYear(), employee_id: '' })
const loading = ref(false)

const employees = ref([])

const showModal = ref(false)
const editingId = ref(null)
const saving = ref(false)
const formError = ref('')
const form = reactive({
  employee_id: '',
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
  base_salary: 0,
  bonus: 0,
  note: ''
})

const formatMoney = (value) => {
  return Number(value || 0).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}

const loadEmployees = async () => {
  if (!isAdmin.value) return
  try {
    const res = await employeesService.getEmployees({ per_page: 100, status: 'Đang làm việc' })
    employees.value = res.data || []
  } catch (err) {
    console.error(err)
  }
}

const loadData = async (page = 1) => {
  loading.value = true
  try {
    const params = { page, per_page: meta.value.per_page, month: filters.month, year: filters.year }
    if (isAdmin.value) params.employee_id = filters.employee_id
    const res = isAdmin.value ? await salaryService.list(params) : await salaryService.mySalaries(params)
    rows.value = res.data
    meta.value = res.meta
  } catch (err) {
    console.error(err)
    alert('Failed to load salary records')
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  form.employee_id = employees.value[0]?.id || ''
  form.month = new Date().getMonth() + 1
  form.year = new Date().getFullYear()
  form.base_salary = 0
  form.bonus = 0
  form.note = ''
  formError.value = ''
  showModal.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  form.employee_id = row.employee_id
  form.month = row.month
  form.year = row.year
  form.base_salary = row.base_salary
  form.bonus = row.bonus
  form.note = row.note || ''
  formError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const save = async () => {
  formError.value = ''
  saving.value = true
  try {
    if (!form.employee_id) {
      formError.value = 'Please select an employee'
      return
    }
    const payload = { ...form }
    if (editingId.value) {
      await salaryService.update(editingId.value, payload)
    } else {
      await salaryService.create(payload)
    }
    showModal.value = false
    await loadData(meta.value.current_page)
  } catch (err) {
    console.error(err)
    formError.value = err.response?.data?.message || 'Failed to save'
  } finally {
    saving.value = false
  }
}

const remove = async (id) => {
  if (!confirm('Are you sure you want to delete this salary record?')) return
  try {
    await salaryService.remove(id)
    await loadData(meta.value.current_page)
  } catch (err) {
    console.error(err)
    alert('Failed to delete')
  }
}

onMounted(async () => {
  await loadEmployees()
  await loadData()
})
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-dialog {
  width: 90%;
  max-width: 600px;
}

.modal-content {
  border-radius: 8px;
  border: none;
}

.table thead th {
  border-bottom: 1px solid #E0E0E0 !important;
}

.table tbody td {
  border-bottom: 1px solid #F0F0F0;
}
</style>
