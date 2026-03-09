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
              currentPath === tab.path
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
      <!-- Success Notification Toast -->
      <div
        v-if="showNotification"
        class="fixed top-20 right-4 z-50 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 animate-slide-in"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span class="font-medium">{{ notificationMessage }}</span>
        <button @click="showNotification = false" class="ml-4 hover:bg-green-600 rounded p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Employee List</h2>
          <div class="flex gap-4 items-center">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search"
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <button
              @click="showAddModal = true"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
            >
              <span>Add Employee</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-green-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Username</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Contact No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Purok</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Last Login</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="employee in filteredEmployees" :key="employee.admin_id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ employee.fname }} {{ employee.mname ? employee.mname.charAt(0) + '.' : '' }} {{ employee.lname }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  <span
                    :class="{
                      'bg-purple-100 text-purple-800': employee.role_name === 'President',
                      'bg-blue-100 text-blue-800': employee.role_name === 'Treasurer',
                      'bg-green-100 text-green-800': employee.role_name === 'Collector',
                      'bg-gray-100 text-gray-800': !['President', 'Treasurer', 'Collector'].includes(employee.role_name)
                    }"
                    class="px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ employee.role_name }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ employee.username }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ employee.contact_no || '-' }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ employee.purok_name || '-' }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(employee.last_login) }}</td>
                <td class="px-6 py-4 text-sm">
                  <div class="flex gap-2">
                    <button
                      @click="editEmployee(employee.admin_id)"
                      class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                    >
                      Edit
                    </button>
                    <button
                      v-if="employee.role_name !== 'President'"
                      @click="deleteEmployee(employee.admin_id)"
                      class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="loading" class="text-center py-12">
          <p class="text-gray-600">Loading employees...</p>
        </div>

        <div v-if="!loading && filteredEmployees.length === 0" class="text-center py-12">
          <p class="text-gray-600">No employees found</p>
        </div>
      </div>
    </main>    <!-- Add Employee Modal -->
    <div v-if="showAddModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full my-8" @click.stop>
        <div class="p-6 border-b">
          <div class="flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-800">Add New Employee</h3>
            <button @click="closeAddModal" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <form @submit.prevent="saveEmployee" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Personal Information -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                <input
                  v-model="newEmployee.fname"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                <input
                  v-model="newEmployee.mname"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                <input
                  v-model="newEmployee.lname"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                <input
                  v-model="newEmployee.suffix"
                  type="text"
                  placeholder="Jr., Sr., III"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                <input
                  v-model="newEmployee.contact_no"
                  type="text"
                  maxlength="11"
                  placeholder="09XX XXX XXXX"
                  pattern="^09\d{9}$"
                  title="Please enter a valid 11-digit mobile number starting with '09'"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  @input="validateContactNumber"
                />
                <p v-if="contactError" class="mt-1 text-sm text-red-600">{{ contactError }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                <input
                  v-model="newEmployee.username"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                <input
                  v-model="newEmployee.password"
                  type="password"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Purok</label>
                <select
                  v-model="newEmployee.purok_id"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                >
                  <option value="">Select Purok</option>
                  <option v-for="purok in puroks" :key="purok.purok_id" :value="purok.purok_id">
                    {{ purok.purok }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                <select
                  v-model="newEmployee.role_id"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                >
                  <option value="">Select Role</option>
                  <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                    {{ role.role_name }}
                  </option>
                </select>
              </div>
            </div>

            <div v-if="modalError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-6">
              {{ modalError }}
            </div>

            <div class="flex justify-end gap-4 mt-6">
              <button
                type="button"
                @click="closeAddModal"
                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-400"
              >
                <span v-if="saving">Saving...</span>
                <span v-else>Save Employee</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const api = useApi()

const currentPath = computed(() => route.path)

const tabs = [
  { label: 'Dashboard', path: '/president/dashboard' },
  { label: 'Members', path: '/president/members' },
  { label: 'Expenses', path: '/president/expenses' },
  { label: 'Master-list', path: '/president/master-list' },
  { label: 'Collection', path: '/president/collection' },
  { label: 'Payment Status', path: '/president/payment-status' },
  { label: 'Important Info', path: '/president/important-info' },
  { label: 'Employee', path: '/president/employee' }
]

const employees = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showAddModal = ref(false)
const showNotification = ref(false)
const notificationMessage = ref('')

const puroks = ref([])
const roles = ref([])
const modalError = ref('')
const contactError = ref('')
const saving = ref(false)

const newEmployee = ref({
  fname: '',
  mname: '',
  lname: '',
  suffix: '',
  contact_no: '',
  username: '',
  password: '',
  purok_id: '',
  role_id: '',
  association_id: 1 // Default value
})

const validateContactNumber = () => {
  if (!newEmployee.value.contact_no) {
    contactError.value = ''
    return
  }

  const contactRegex = /^09\d{9}$/
  if (!contactRegex.test(newEmployee.value.contact_no)) {
    contactError.value = 'Please enter a valid 11-digit mobile number starting with 09'
    newEmployee.value.contact_no = newEmployee.value.contact_no.slice(0, 11)
  } else {
    contactError.value = ''
  }
}

const closeAddModal = () => {
  showAddModal.value = false
  modalError.value = ''
  contactError.value = ''
  newEmployee.value = {
    fname: '',
    mname: '',
    lname: '',
    suffix: '',
    contact_no: '',
    username: '',
    password: '',
    purok_id: '',
    role_id: '',
    association_id: 1
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

const saveEmployee = async () => {
  if (saving.value) return
  saving.value = true
  modalError.value = ''

  try {
    await api('/users', {
      method: 'POST',
      body: newEmployee.value
    })
    
    // Show success notification
    notificationMessage.value = 'Employee added successfully!'
    showNotification.value = true
    
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)

    // Close modal and refresh list
    closeAddModal()
    loadEmployees()
  } catch (err) {
    modalError.value = err.data?.message || 'Failed to save employee. Please try again.'
  } finally {
    saving.value = false
  }
}

const filteredEmployees = computed(() => {
  if (!searchQuery.value) return employees.value

  const query = searchQuery.value.toLowerCase()
  return employees.value.filter(e => 
    e.fname.toLowerCase().includes(query) ||
    e.lname.toLowerCase().includes(query) ||
    e.username.toLowerCase().includes(query) ||
    e.role_name.toLowerCase().includes(query)
  )
})

const loadEmployees = async () => {
  loading.value = true
  try {
    const data = await api('/users')
    employees.value = data
  } catch (err) {
    console.error('Failed to load employees:', err)
  } finally {
    loading.value = false
  }
}

const editEmployee = (id) => {
  router.push(`/president/employee-edit/${id}`)
}

const deleteEmployee = async (id) => {
  if (!confirm('Are you sure you want to delete this employee?')) return

  try {
    await api(`/users/${id}`, { method: 'DELETE' })
    await loadEmployees()
    
    // Show success notification
    notificationMessage.value = 'Employee deleted successfully!'
    showNotification.value = true
    
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    alert('Failed to delete employee')
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

onMounted(() => {
  loadEmployees()
  loadPuroks()
  loadRoles()
  
  // Check for success notification from sessionStorage
  const successMsg = sessionStorage.getItem('employeeUpdateSuccess')
  if (successMsg) {
    notificationMessage.value = successMsg
    showNotification.value = true
    sessionStorage.removeItem('employeeUpdateSuccess')
    
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  }
})
</script>

<style scoped>
@keyframes slide-in {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
}
</style>