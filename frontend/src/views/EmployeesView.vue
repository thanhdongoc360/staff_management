<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Quản lý nhân viên</h2>
      <button class="btn btn-primary" @click="openCreate">
        <i class="bi bi-plus"></i> Thêm nhân viên
      </button>
    </div>

    <!-- Search Bar -->
    <div class="mb-4">
      <input 
        v-model="filters.search" 
        type="text" 
        class="form-control form-control-lg" 
        placeholder="Tìm kiếm theo tên, email, vị trí hoặc phòng ban..."
        @keyup.enter="fetchEmployees()"
        style="background-color: #F5F5F5; border: 1px solid #E0E0E0;"
      />
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="font-weight: 600; color: #666;">Họ và tên</th>
              <th style="font-weight: 600; color: #666;">Vị trí</th>
              <th style="font-weight: 600; color: #666;">Phòng ban</th>
              <th style="font-weight: 600; color: #666;">Email</th>
              <th style="font-weight: 600; color: #666;">Trạng thái</th>
              <th style="font-weight: 600; color: #666;">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="employees.length === 0">
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-inbox"></i> Không tìm thấy nhân viên
              </td>
            </tr>
            <tr v-for="emp in employees" :key="emp.id">
              <td class="fw-500">{{ emp.name }}</td>
              <td>{{ emp.position }}</td>
              <td>{{ emp.department }}</td>
              <td>{{ emp.email }}</td>
              <td>
                <span :class="['badge', emp.status === 'Active' || emp.status === 'Đang làm việc' ? 'bg-success' : 'bg-secondary']">
                  {{ emp.status }}
                </span>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-link text-primary p-0" @click="viewEmployee(emp)" title="Xem">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="btn btn-sm btn-link text-primary p-0" @click="openEdit(emp)" title="Chỉnh sửa">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-link text-danger p-0" @click="confirmDelete(emp)" title="Xóa">
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
            <h5 class="modal-title">{{ editingId ? 'Chỉnh sửa nhân viên' : 'Thêm nhân viên' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Họ và tên *</label>
                <input v-model="form.name" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input v-model="form.email" type="email" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Vị trí *</label>
                <input v-model="form.position" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Phòng ban *</label>
                <input v-model="form.department" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Mật khẩu {{ editingId ? '(để trống nếu không thay đổi)' : '*' }}</label>
                <input v-model="form.password" type="password" class="form-control" :placeholder="editingId ? 'Không thay đổi' : 'Tối thiểu 6 ký tự'" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Trạng thái</label>
                <select v-model="form.status" class="form-select">
                  <option value="Active">Đang làm việc</option>
                  <option value="Inactive">Nghỉ việc</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeModal">Đóng</button>
            <button class="btn btn-primary" :disabled="saving" @click="saveEmployee">
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
import { ref, onMounted } from 'vue'
import AppLayout from '../components/layouts/AppLayout.vue'
import employeesService from '../services/employeesService'

const employees = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ status: '', search: '', per_page: 10 })
const loading = ref(false)
const error = ref('')
const success = ref('')
const showModal = ref(false)
const editingId = ref(null)
const saving = ref(false)
const formError = ref('')

const form = ref({
  name: '',
  email: '',
  position: '',
  department: '',
  password: '',
  status: 'Active'
})

const defaultForm = {
  name: '',
  email: '',
  position: '',
  department: '',
  password: '',
  status: 'Active'
}

const fetchEmployees = async () => {
  loading.value = true
  try {
    const data = await employeesService.getEmployees({
      page: filters.value.page || 1,
      per_page: filters.value.per_page,
      search: filters.value.search,
      status: filters.value.status
    })
    employees.value = data.data || data
    if (data.meta) {
      pagination.value = data.meta
    }
    error.value = ''
  } catch (err) {
    error.value = err.message || 'Failed to load employees'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  form.value = { ...defaultForm }
  formError.value = ''
  showModal.value = true
}

const openEdit = (emp) => {
  editingId.value = emp.id
  form.value = {
    name: emp.name,
    email: emp.email,
    position: emp.position,
    department: emp.department,
    password: '',
    status: emp.status
  }
  formError.value = ''
  showModal.value = true
}

const viewEmployee = (emp) => {
  // TODO: Implement view employee details
  console.log('View employee:', emp)
}

const closeModal = () => {
  showModal.value = false
  form.value = { ...defaultForm }
  formError.value = ''
}

const saveEmployee = async () => {
  if (!form.value.name || !form.value.email || !form.value.position || !form.value.department) {
    formError.value = 'Please fill in all required fields'
    return
  }

  saving.value = true
  try {
    if (editingId.value) {
      await employeesService.updateEmployee(editingId.value, form.value)
      success.value = 'Employee updated successfully'
    } else {
      await employeesService.createEmployee(form.value)
      success.value = 'Employee created successfully'
    }
    closeModal()
    await fetchEmployees()
  } catch (err) {
    formError.value = err.message || 'Failed to save employee'
  } finally {
    saving.value = false
  }
}

const confirmDelete = (emp) => {
  if (confirm(`Are you sure you want to delete ${emp.name}?`)) {
    deleteEmployee(emp.id)
  }
}

const deleteEmployee = async (id) => {
  try {
    await employeesService.deleteEmployee(id)
    success.value = 'Employee deleted successfully'
    await fetchEmployees()
  } catch (err) {
    error.value = err.message || 'Failed to delete employee'
  }
}

const changePage = (page) => {
  filters.value.page = page
  fetchEmployees()
}

onMounted(() => {
  fetchEmployees()
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
  max-width: 500px;
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

.badge {
  font-weight: 500;
  font-size: 12px;
  padding: 4px 8px;
}
</style>
