<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  // Attempt to fetch user on app mount if a token exists
  // This handles refreshing the page and staying logged in
  if (authStore.token) {
    authStore.fetchUser()
  }
})
</script>

<template>
  <router-view />
</template>