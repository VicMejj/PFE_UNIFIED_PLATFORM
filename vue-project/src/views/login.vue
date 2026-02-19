<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const email = ref('')
const password = ref('')
const error = ref('')
const router = useRouter()

const login = async () => {
  try {
    const res = await axios.post('http://127.0.0.1:8000/api/v1/login', {
      email: email.value,
      password: password.value
    })

    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))

    const role = res.data.user.role

    if (role === 'admin') router.push('/admin')
    else if (role === 'rh_manager') router.push('/rh')
    else router.push('/employee')

  } catch (e: any) {
    error.value = 'Invalid credentials'
  }
}
</script>

<template>
  <div class="page">
    <div class="card">
      <h2>Login</h2>

      <input v-model="email" type="email" placeholder="Email" />
      <input v-model="password" type="password" placeholder="Password" />

      <p v-if="error" class="error">{{ error }}</p>

      <button @click="login">Login</button>

      <p class="text">
        No account?
        <router-link to="/register">Register</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.page {
  @apply min-h-screen flex items-center justify-center bg-gray-100;
}
.card {
  @apply bg-white p-8 rounded-xl shadow-md w-96;
}
h2 {
  @apply text-2xl font-bold mb-6 text-center;
}
input {
  @apply w-full mb-4 p-3 border rounded;
}
button {
  @apply w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700;
}
.error {
  @apply text-red-500 text-sm mb-3;
}
.text {
  @apply text-center text-sm mt-4;
}
</style>