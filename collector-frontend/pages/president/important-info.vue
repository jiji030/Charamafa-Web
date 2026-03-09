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
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold text-gray-800">Bill Information</h2>
          <button
            v-if="!isEditing"
            @click="isEditing = true"
            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
          </button>
        </div>

        <form @submit.prevent="saveInfo" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Miscellaneous Charges (₱)
              </label>
              <input
                v-model.number="form.miscellaneous"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Minimum Amount per Monthly (₱)
              </label>
              <input
                v-model.number="form.minimum_amount_per_month"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Excess Amount per Cubic (₱)
              </label>
              <input
                v-model.number="form.excess_minimum_CUM_per_month"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Loss/Damage & Other Charges (₱)
              </label>
              <input
                v-model.number="form.lossdamage_and_other_charges"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Electricity Consumption (₱)
              </label>
              <input
                v-model.number="form.electricity_consumption"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Generator Consumption (₱)
              </label>
              <input
                v-model.number="form.generator_consumption"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Free C.U.M per Month
              </label>
              <input
                v-model.number="form.free_CUM_per_month"
                type="number"
                step="1"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Connector Damage with Unknown Person (₱)
              </label>
              <input
                v-model.number="form.connector_damage_with_unknown_person"
                type="number"
                step="0.01"
                min="0"
                :disabled="!isEditing"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              />
            </div>
          </div>          <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">INVOICE Number Management</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Starting INVOICE Number
                </label>
                <input
                  v-model="orForm.start_number"
                  type="text"
                  placeholder="e.g., 00500"
                  :disabled="!isEditing"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Ending INVOICE Number
                </label>
                <input
                  v-model="orForm.end_number"
                  type="text"
                  placeholder="e.g., 00700"
                  :disabled="!isEditing"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
                />
              </div>
            </div>

            <div v-if="isEditing" class="mb-6">
              <button
                type="button"
                @click="createOrBooklet"
                :disabled="!orForm.start_number || !orForm.end_number || creatingBooklet"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors disabled:bg-gray-400"
              >
                {{ creatingBooklet ? 'Creating...' : 'Generate INVOICE Numbers' }}
              </button>
            </div>

            <div v-if="orBooklets.length > 0" class="mb-6">
              <h4 class="text-md font-semibold text-gray-700 mb-3">INVOICE Booklets History</h4>
              <div class="bg-gray-50 rounded-lg p-4 space-y-3 max-h-60 overflow-y-auto">
                <div
                  v-for="booklet in orBooklets"
                  :key="booklet.booklet_id"
                  class="bg-white p-3 rounded border flex justify-between items-center"
                >
                  <div>
                    <p class="font-medium text-gray-800">
                      INVOICE #{{ booklet.start_number }} - #{{ booklet.end_number }}
                    </p>
                    <p class="text-sm text-gray-600">
                      Total: {{ booklet.total_numbers }} | 
                      Used: {{ booklet.used_numbers }} | 
                      Available: {{ booklet.available_numbers }}
                    </p>
                    <p class="text-xs text-gray-500">
                      Created: {{ formatDate(booklet.created_at) }}
                    </p>
                  </div>
                  <div
                    :class="[
                      'px-3 py-1 rounded-full text-xs font-semibold',
                      booklet.available_numbers === 0 
                        ? 'bg-red-100 text-red-800'
                        : booklet.available_numbers <= 5
                        ? 'bg-yellow-100 text-yellow-800'
                        : 'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ booklet.available_numbers === 0 ? 'Depleted' : booklet.available_numbers <= 5 ? 'Low Stock' : 'Active' }}
                  </div>
                </div>
              </div>
            </div>
            
            <div class="border-t pt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Pabilin
              </label>
              <textarea
                v-model="form.announcement"
                rows="4"
                :disabled="!isEditing"
                placeholder="Sa tanang mga konsumador kamo among gi-abangan nga pagbayaran na ko sa tubig para malikayan and pag-ot ug serbisyo niini."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100"
              ></textarea>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ error }}
          </div>

          <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ success }}
          </div>

          <div v-if="isEditing" class="flex gap-4">
            <button
              type="submit"
              :disabled="loading"
              class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors disabled:bg-gray-400"
            >
              {{ loading ? 'Saving...' : 'Save Changes' }}
            </button>
            <button
              type="button"
              @click="cancelEdit"
              class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-6 rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </main>
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

const form = ref({
  minimum_amount_per_month: 160,
  excess_minimum_CUM_per_month: 15,
  lossdamage_and_other_charges: 0,
  electricity_consumption: 0,
  generator_consumption: 0,
  announcement: '',
  free_CUM_per_month: 0,
  miscellaneous: 0,
  connector_damage_with_unknown_person: 0
})

const orForm = ref({
  start_number: '',
  end_number: ''
})

const orBooklets = ref([])
const creatingBooklet = ref(false)

const originalForm = ref({})
const isEditing = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')
const authInitialized = ref(false)

const infoId = ref(null)

const loadInfo = async () => {
  try {
    console.log('Loading important information...')
    const data = await api('/important-information')
    // console.log('Received data:', data)
    if (data && data.id) {
      form.value = { ...data }
      originalForm.value = { ...data }
      infoId.value = data.id
    }
    
    // Load OR booklets
    await loadOrBooklets()
  } catch (err) {
    console.error('Failed to load information:', err)
    console.error('Error details:', err.response || err)
  }
}

const loadOrBooklets = async () => {
  try {
    const booklets = await api('/or-booklets')
    orBooklets.value = booklets
  } catch (err) {
    console.error('Failed to load INVOICE booklets:', err)
  }
}

const createOrBooklet = async () => {
  if (!orForm.value.start_number || !orForm.value.end_number) {
    error.value = 'Please enter both start and end INVOICE numbers'
    return
  }

  const start = parseInt(orForm.value.start_number)
  const end = parseInt(orForm.value.end_number)

  if (start >= end) {
    error.value = 'Start number must be less than end number'
    return
  }

  creatingBooklet.value = true
  error.value = ''
  success.value = ''

  try {
    const result = await api('/or-booklets', {
      method: 'POST',
      body: {
        start_number: orForm.value.start_number,
        end_number: orForm.value.end_number,
        created_by: authStore.user.admin_id
      }
    })

    success.value = `Successfully created ${result.total_numbers} INVOICE numbers (${orForm.value.start_number} - ${orForm.value.end_number})`
    
    // Reset form
    orForm.value.start_number = ''
    orForm.value.end_number = ''
    
    // Reload booklets
    await loadOrBooklets()

    setTimeout(() => {
      success.value = ''
    }, 5000)
  } catch (err) {
    console.error('Create INVOICE booklet error:', err)
    error.value = err.message || 'Failed to create INVOICE booklet. Please try again.'
  } finally {
    creatingBooklet.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const saveInfo = async () => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    let result
    if (infoId.value) {
      // Update existing record
      result = await api(`/important-information/${infoId.value}`, {
        method: 'PUT',
        body: form.value
      })
    } else {
      // Create new record
      result = await api('/important-information', {
        method: 'POST',
        body: form.value
      })
      if (result && result.id) {
        infoId.value = result.id
      }
    }

    success.value = 'Information updated successfully!'
    originalForm.value = { ...form.value }
    isEditing.value = false

    setTimeout(() => {
      success.value = ''
    }, 3000)
  } catch (err) {
    console.error('Save error:', err)
    error.value = err.message || 'Failed to update information. Please try again.'
  } finally {
    loading.value = false
  }
}

const cancelEdit = () => {
  form.value = { ...originalForm.value }
  isEditing.value = false
  error.value = ''
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

onMounted(() => {
  loadInfo()
})
</script>