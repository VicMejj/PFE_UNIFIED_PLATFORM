<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const name = ref('')
const email = ref('')
const password = ref('')
const role = ref('employee')
const router = useRouter()

const register = async () => {
  await axios.post('http://127.0.0.1:8000/api/v1/register', {
    name: name.value,
    email: email.value,
    password: password.value,
    role: role.value
  })

  router.push('/login')
}
</script>

<template>
  <div class="page">
    <div class="card">
      <h2>Register</h2>

      <input v-model="name" placeholder="Name" />
      <input v-model="email" placeholder="Email" />
      <input v-model="password" type="password" placeholder="Password" />

      <select v-model="role">
        <option value="employee">Employee</option>
        <option value="rh_manager">RH Manager</option>
        <option value="user">User</option>
      </select>

      <button @click="register">Create Account</button>
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
input, select {
  @apply w-full mb-4 p-3 border rounded;
}
button {
  @apply w-full bg-green-600 text-white py-2 rounded hover:bg-green-700;
}
</style>