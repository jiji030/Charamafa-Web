<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-green-700 text-white shadow-md">
      <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center overflow-hidden">
              <img src="/charmafa-logo.png" alt="CHARMAFA" class="w-10 h-10 object-contain" />
            </div>
            <div>
              <h1 class="text-2xl font-bold">CHARMAFA</h1>
              <p class="text-sm text-green-100">A Waterworks Service Association</p>
            </div>
          </div>
          <button
            @click="handleLogout"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
      <div class="container mx-auto px-4">
        <div class="flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.path"
            @click="navigateTo(tab.path)"
            :class="[
              'px-6 py-3 font-medium transition-colors',
              currentPath.startsWith(tab.path)
                ? 'text-green-700 border-b-2 border-green-700'
                : 'text-gray-600 hover:text-green-600'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
      <!-- Back Button -->
      <button
        @click="navigateTo('/president/employee')"
        class="mb-6 flex items-center gap-2 text-green-700 hover:text-green-800 font-medium"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Employee List
      </button>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b bg-green-50">
          <h2 class="text-2xl font-bold text-gray-800">Edit Employee Information</h2>
          <p class="text-sm text-gray-600 mt-1">Update employee details below</p>
        </div>

        <div v-if="loading" class="p-12 text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-700 mx-auto mb-4"></div>
          <p class="text-gray-600">Loading employee data...</p>
        </div>

        <div v-else-if="loadError" class="p-12 text-center">
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg inline-block">
            {{ loadError }}
          </div>
        </div>

        <div v-else class="p-6">
          <form @submit.prevent="updateEmployee" class="space-y-6">
            <!-- Personal Information -->
            <div>
              <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Personal Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                  <input
                    v-model="employee.fname"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                  <input
                    v-model="employee.mname"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                  <input
                    v-model="employee.lname"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                  <input
                    v-model="employee.suffix"
                    type="text"
                    placeholder="Jr., Sr., III"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Contact No</label>
                  <input
                    v-model="employee.contact_no"
                    type="text"
                    placeholder="09XX XXX XXXX"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Purok</label>
                  <select
                    v-model="employee.purok_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  >
                    <option :value="null">Select Purok</option>
                    <option v-for="purok in puroks" :key="purok.purok_id" :value="purok.purok_id">
                      {{ purok.purok }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Account Information -->
            <div>
              <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Account Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                  <input
                    v-model="employee.username"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                  <select
                    v-model="employee.role_id"
                    required
                    :disabled="employee.role_id === 1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                  >
                    <option value="">Select Role</option>
                    <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                      {{ role.role_name }}
                    </option>
                  </select>
                  <p v-if="employee.role_id === 1" class="text-xs text-gray-500 mt-1">President role cannot be changed</p>
                </div>
              </div>
            </div>

            <!-- Password Change (Optional) -->
            <div>
              <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Change Password (Optional)</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                  <input
                    v-model="newPassword"
                    type="password"
                    placeholder="Leave blank to keep current password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                  <input
                    v-model="confirmPassword"
                    type="password"
                    placeholder="Confirm new password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
              </div>
            </div>

            <!-- Last Login Info (Read-only) -->
            <div>
              <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Login Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Last Login</label>
                  <input
                    :value="formatDate(employee.last_login)"
                    type="text"
                    readonly
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Last Date Synced</label>
                  <input
                    :value="formatDate(employee.last_date_synced)"
                    type="text"
                    readonly
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                  />
                </div>
              </div>
            </div>

            <div v-if="saveError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
              {{ saveError }}
            </div>

            <div class="flex gap-4 pt-4 border-t">
              <button
                type="submit"
                :disabled="saving"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
              >
                {{ saving ? 'Updating...' : 'Update Employee' }}
              </button>
              <button
                type="button"
                @click="navigateTo('/president/employee')"
                :disabled="saving"
                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-200"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useRoute } from '#app'
import { useApi } from '~/composables/useApi'

const authStore = useAuthStore()
const route = useRoute()
const api = useApi()

const employee = ref({})
const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const saveError = ref('')
const puroks = ref([])
const roles = ref([])
const newPassword = ref('')
const confirmPassword = ref('')

const employeeId = computed(() => route.params.id)
const currentPath = computed(() => route.path)

const tabs = [
  { label: 'Members', path: '/president/members' },
  { label: 'Expenses', path: '/president/expenses' },
  { label: 'Master-list', path: '/president/master-list' },
  { label: 'Collection', path: '/president/collection' },
  { label: 'Payment Status', path: '/president/payment-status' },
  { label: 'Important Info', path: '/president/important-info' },
  { label: 'Employee', path: '/president/employee' }
]

const loadEmployee = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const data = await api(`/users/${employeeId.value}`)
    employee.value = data
  } catch (err) {
    console.error('Failed to load employee:', err)
    loadError.value = err.message || 'Failed to load employee data'
  } finally {
    loading.value = false
  }
}

const loadPuroks = async () => {
  try {
    puroks.value = await api('/puroks')
  } catch (err) {
    console.error('Failed to load puroks:', err)
  }
}

const loadRoles = async () => {
  try {
    roles.value = await api('/roles')
  } catch (err) {
    console.error('Failed to load roles:', err)
  }
}

const updateEmployee = async () => {
  saving.value = true
  saveError.value = ''

  // Validate password if provided
  if (newPassword.value || confirmPassword.value) {
    if (newPassword.value !== confirmPassword.value) {
      saveError.value = 'Passwords do not match'
      saving.value = false
      return
    }
    if (newPassword.value.length < 4) {
      saveError.value = 'Password must be at least 6 characters'
      saving.value = false
      return
    }
  }

  try {
    const updateData = {
      fname: employee.value.fname,
      mname: employee.value.mname,
      lname: employee.value.lname,
      suffix: employee.value.suffix,
      contact_no: employee.value.contact_no,
      username: employee.value.username,
      purok_id: employee.value.purok_id,
      role_id: employee.value.role_id
    }

    // Add password only if provided
    if (newPassword.value) {
      updateData.password = newPassword.value
    }

    await api(`/users/${employeeId.value}`, {
      method: 'PUT',
      body: updateData
    })

    // Store success message in sessionStorage
    sessionStorage.setItem('employeeUpdateSuccess', 'Employee updated successfully!')
    
    // Redirect to employee list
    navigateTo('/president/employee')
  } catch (err) {
    saveError.value = err.message || 'Failed to update employee. Please try again.'
    console.error('Failed to update employee:', err)
  } finally {
    saving.value = false
  }
}

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

onMounted(() => {
  authStore.initAuth()
  loadEmployee()
  loadPuroks()
  loadRoles()
})
</script>