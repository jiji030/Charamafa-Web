<template>
  <div v-if="storeReady" class="min-h-screen flex">
    <!-- Left section with image and text (70%) -->
    <div class="hidden lg:flex lg:w-[70%] bg-green-700 relative items-center justify-center">
      <div class="flex items-center gap-8">
        <img 
          src="/charmafa-logo.png" 
          alt="CHARMAFA Logo"
          class="w-80 h-80 object-contain"
        />
        <div class="text-white">
          <h1 class="text-5xl font-bold mb-2">CHARMAFA</h1>
          <p class="text-xl text-green-100">every drop counts</p>
        </div>
      </div>
    </div>

    <!-- Right section with login form (30%) -->
    <div class="w-full lg:w-[30%] flex items-center justify-center p-8 bg-white">
      <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
          <!-- Mobile logo -->
          <div class="lg:hidden mb-6">
            <img 
              src="/charmafa-logo.png" 
              alt="CHARMAFA Logo"
              class="w-32 h-32 mx-auto object-contain"
            />
            <h2 class="text-2xl font-bold text-green-700 mt-4">CHARMAFA</h2>
            <p class="text-sm text-green-600">every drop counts</p>
          </div>
          <h1 class="text-2xl font-bold text-gray-800 mb-2">Hello Again!</h1>
          <p class="text-sm text-gray-600">Welcome back</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
              Username
            </label>
            <input
              id="username"
              v-model="credentials.username"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              placeholder="Enter your username"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <input
              id="password"
              v-model="credentials.password"
              type="password"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              placeholder="Enter your password"
            />
          </div>

          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ error }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors disabled:bg-gray-400"
          >
            <span v-if="loading">Signing in...</span>
            <span v-else>Sign In</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Optional loading skeleton -->
  <div v-else class="flex items-center justify-center min-h-screen">
    Loading...
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuthStore } from '~/stores/auth'

// Remove the auth middleware from the login page
// definePageMeta({
//   middleware: 'auth'
// })

const router = useRouter()
const authStore = useAuthStore()

// Add the checkMeterReset function using the useApi composable
const checkMeterReset = async () => {
  try {
    const api = useApi()
    const response = await api('/reset-meters')
    // console.log('Meter reset check:', response.message)
  } catch (error) {
    console.error('Failed to check meter reset:', error)
  }
}

const credentials = ref({ username: '', password: '' })
const loading = ref(false)
const error = ref('')
const storeReady = ref(false) // Wait until store is hydrated

// Ensure store is loaded before rendering UI
onMounted(async () => {
  // Initialize auth first
  if (process.client) {
    authStore.initAuth()
  }

  // Check for meter reset if authenticated
  // if (authStore.isAuthenticated) {
  //   await checkMeterReset()
  // }

  // If already logged in, redirect based on role
  if (authStore.user) {
    redirectByRole(authStore.user.role_id)
    return
  }

  storeReady.value = true
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  try {
    // console.log('Attempting login with:', credentials.value.username)
    await authStore.login(credentials.value)
    // console.log('Login successful, user:', authStore.user)
    // console.log('Token:', authStore.token)
    redirectByRole(authStore.user.role_id)
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.data?.message || err.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }
}

const redirectByRole = (roleId) => {
  if (roleId === 3) router.push('/collector/dashboard')
  else if (roleId === 2) router.push('/treasurer/master-list')
  else if (roleId === 1) router.push('/president/dashboard')
  else error.value = 'Invalid user role'
}
</script>
