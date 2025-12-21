<template>
  <AppLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">Thông báo</h2>
        <p class="text-muted mb-0">Xem lịch sử thông báo và đánh dấu đã đọc</p>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <span class="badge" :class="unreadCount ? 'bg-warning' : 'bg-secondary'">Chưa đọc: {{ unreadCount }}</span>
        <button class="btn btn-outline-secondary btn-sm" @click="markAll" :disabled="loading || unreadCount === 0">
          <i class="bi bi-check-all"></i> Đánh dấu tất cả đã đọc
        </button>
      </div>
    </div>

    <!-- Notifications Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="font-weight: 600; color: #666;">Tiêu đề</th>
              <th style="font-weight: 600; color: #666;">Nội dung</th>
              <th style="font-weight: 600; color: #666;">Thời gian</th>
              <th style="font-weight: 600; color: #666;">Trạng thái</th>
              <th style="font-weight: 600; color: #666;">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="!items.length">
              <td colspan="5" class="text-center py-4 text-muted">
                <i class="bi bi-inbox"></i> Không có thông báo
              </td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td class="fw-500">{{ item.title }}</td>
              <td>{{ item.content }}</td>
              <td>{{ item.date }}</td>
              <td>
                <span :class="['badge', item.is_read ? 'bg-secondary' : 'bg-primary']">
                  {{ item.is_read ? 'Đã đọc' : 'Chưa đọc' }}
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-primary" @click="markOne(item)" :disabled="item.is_read">
                  <i class="bi bi-check"></i> Đánh dấu đã đọc
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import AppLayout from '../components/layouts/AppLayout.vue'
import { notificationsService } from '../services/notificationsService'
import { useAuthStore } from '../stores/auth'
import { getEcho } from '../echo'

const authStore = useAuthStore()
let channel = null

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const unreadCount = ref(0)
const loading = ref(false)

const loadUnread = async () => {
  try {
    unreadCount.value = await notificationsService.unreadCount()
  } catch (err) {
    console.error(err)
  }
}

const load = async (page = 1) => {
  loading.value = true
  try {
    const res = await notificationsService.list(page)
    items.value = res.data
    meta.value = res.meta
    unreadCount.value = res.unread_count ?? unreadCount.value
  } catch (err) {
    console.error(err)
    alert('Không thể tải thông báo')
  } finally {
    loading.value = false
  }
}

const markOne = async (item) => {
  if (item.is_read) return
  try {
    await notificationsService.markAsRead(item.id)
    item.is_read = true
    if (unreadCount.value > 0) unreadCount.value -= 1
  } catch (err) {
    console.error(err)
    alert('Không thể đánh dấu')
  }
}

const markAll = async () => {
  try {
    await notificationsService.markAllAsRead()
    items.value = items.value.map((n) => ({ ...n, is_read: true }))
    unreadCount.value = 0
  } catch (err) {
    console.error(err)
    alert('Không thể đánh dấu tất cả')
  }
}

const setupRealtime = () => {
  const echo = getEcho()
  if (!echo || !authStore.user?.id) return

  channel = echo.private(`notifications.${authStore.user.id}`)
    .listen('.notification.created', (payload) => {
      items.value.unshift(payload)
      unreadCount.value += 1
    })
}

onMounted(() => {
  load()
  loadUnread()
  setupRealtime()
})

onBeforeUnmount(() => {
  if (channel && channel.unsubscribe) {
    channel.unsubscribe()
  }
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
}
.badge { padding: 6px 10px; border-radius: 999px; font-weight: 700; font-size: 12px; }
.badge--warning { background: #fef9c3; color: #854d0e; }
.badge--muted { background: #e5e7eb; color: #374151; }
.ghost { background: #fff; border: 1px solid #e5e7eb; padding: 8px 10px; border-radius: 8px; cursor: pointer; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
th { background: #f9fafb; font-weight: 700; }
.pill { padding: 6px 10px; border-radius: 999px; font-weight: 600; font-size: 12px; }
.pill--primary { background: #dbeafe; color: #1d4ed8; }
.pill--muted { background: #f3f4f6; color: #374151; }
.pagination { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; }
</style>
