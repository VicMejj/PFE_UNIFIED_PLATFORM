<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ChatBot from '@/components/common/ChatBot.vue'
import GlobalLoadingOverlay from '@/components/common/GlobalLoadingOverlay.vue'

const auth = useAuthStore()

// Track if auth is being initialized
const isInitializing = ref(true)

// Get user from auth store (normalized with proper role mapping)
const user = computed(() => auth.user)

// Show loading overlay during initial auth check
const showLoading = computed(() => isInitializing.value && !!auth.laravelToken)

onMounted(async () => {
  // Initialize auth state from stored token
  await auth.initializeAuth()
  isInitializing.value = false
})
</script>

<template>
  <!-- Global loading overlay for initial auth and route transitions -->
  <GlobalLoadingOverlay :is-visible="showLoading" />

  <!-- Main app content -->
  <RouterView />

  <!-- Show Chatbot only for administrative roles -->
  <ChatBot v-if="user && ['admin', 'rh_manager', 'manager'].includes(user.role)" />
</template>