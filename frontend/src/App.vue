<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  // Verify token on app mount
  if (authStore.isAuthenticated) {
    authStore.fetchUser().catch(() => {
      // Token invalid, will be handled by auth store
    })
  }
})
</script>

<style>
#app {
  min-height: 100vh;
}
</style>
