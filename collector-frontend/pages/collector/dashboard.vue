<template>
  <!-- Show loading while auth is being initialized -->
  <div v-if="!authStore.user && !authInitialized" class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-700 mx-auto mb-4"></div>
      <p class="text-gray-600">Loading...</p>
    </div>
  </div>

  <!-- Main content when auth is ready -->
  <div v-else class="min-h-screen bg-gray-100">
    <!-- Header -->
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
          <div class="flex items-center gap-3">
            <button
              @click="showPrinterSetup = true"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
            >
              <span>🖨️</span>
              <span>Printer Setup</span>
            </button>
            <button
              @click="handleLogout"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
      <!-- Search and Sort Controls -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <div class="flex gap-2">
              <input
                v-model="searchQuery"
                @input="searchMembers"
                type="text"
                placeholder="Search by account no, name, or meter no..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />              <button
                @click="showScanner = true"
                class="bg-green-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap"
              >
                <span>Scan Barcode</span>
              </button>
            </div>
          </div>
          
          <div class="w-full md:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
            <select
              v-model="sortField"
              @change="loadMembers"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="member_id">Member ID</option>
              <option value="account_no">Account No</option>
              <option value="fname">First Name</option>
              <option value="lname">Last Name</option>
            </select>
          </div>
          
          <div class="w-full md:w-40">
            <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
            <select
              v-model="sortOrder"
              @change="loadMembers"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="asc">Ascending</option>
              <option value="desc">Descending</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Members List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div v-if="loading" class="text-center py-12">
          <p class="text-gray-600">Loading members...</p>
        </div>

        <div v-else-if="error" class="text-center py-12">
          <p class="text-red-600">{{ error }}</p>
        </div>

        <div v-else-if="members.length === 0" class="text-center py-12">
          <p class="text-gray-600">No members found</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meter No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purok</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connection</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="member in members"
                :key="member.member_id"
                class="hover:bg-gray-50 cursor-pointer transition-colors"
                @click="viewMember(member)"
              >
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ member.account_no }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ member.fname }} {{ member.mname }} {{ member.lname }} {{ member.suffix }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ member.meter_no }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ member.purok?.purok || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="member.connection_status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ member.connection_status === 1 ? 'Connected' : 'Disconnected' }}
                  </span>
                </td>                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    v-if="member.is_paid && member.prev_balance <= 0"
                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                    :title="`Fully Paid - Balance: ₱${formatMoney(member.prev_balance)}`"
                  >
                    Paid
                  </span>
                  <span
                    v-else-if="member.is_paid && member.prev_balance > 0"
                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"
                    :title="`Partial Payment - Balance: ₱${formatMoney(member.prev_balance)}`"
                  >
                    Partial (₱{{ formatMoney(member.prev_balance) }})
                  </span>
                  <span
                    v-else
                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"
                    :title="`Unpaid - Balance: ₱${formatMoney(member.prev_balance)}`"
                  >
                    Unpaid
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <div class="flex gap-2">
                    <button
                      @click.stop="viewMember(member)"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      View Details →
                    </button>
                    <button
                      v-if="member.connection_status === 1"
                      @click.stop="openDisconnectModal(member)"
                      class="text-red-600 hover:text-red-800 ml-2"
                    >
                      Disconnect
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>    <!-- Barcode Scanner Modal -->
    <div
      v-if="showScanner"
      class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
      @click="closeScanner"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b">
          <h3 class="text-xl font-bold text-gray-800">Scan Barcode</h3>
          <button @click="closeScanner" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Content Area - Scrollable if needed -->
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="scanError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            {{ scanError }}
          </div>

          <div v-if="scanSuccess" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            {{ scanSuccess }}
          </div>          <!-- Camera Scanner -->
          <div id="barcode-scanner" class="w-full rounded-lg overflow-hidden bg-gray-900 relative" style="min-height: 350px; max-height: 450px;"></div>

          <div class="mt-4 text-sm text-gray-600 text-center">
            <p class="font-medium">📱 Position the barcode within the scanning area</p>
            <p class="mt-1">The camera will automatically detect and scan barcodes</p>
            <p class="mt-1 text-xs text-gray-500">Make sure the barcode is well-lit and clearly visible</p>
          </div>
        </div>

        <!-- Footer with Cancel Button -->
        <div class="p-6 border-t bg-gray-50">
          <button
            @click="closeScanner"
            class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Thermal Printer Setup Modal -->
    <ThermalPrinterSetup 
      :show="showPrinterSetup" 
      @close="showPrinterSetup = false" 
    />

    <!-- Disconnect Confirmation Modal -->
    <div
      v-if="showDisconnectModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="closeDisconnectModal"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4" @click.stop>
        <div class="text-center mb-6">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Disconnect Member?</h3>
          <p class="text-gray-600 mb-4">
            Are you sure you want to disconnect:
          </p>
          <div class="bg-gray-50 p-4 rounded-lg mb-4 text-left">
            <p class="font-semibold text-gray-900">{{ selectedMember?.fname }} {{ selectedMember?.lname }}</p>
            <p class="text-sm text-gray-600">Account No: {{ selectedMember?.account_no }}</p>
            <p class="text-sm text-red-600 font-semibold">Balance: ₱{{ formatMoney(selectedMember?.prev_balance) }}</p>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2 text-left">
              Reason for Disconnection
            </label>
            <textarea
              v-model="disconnectReason"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., Unpaid balance, Non-payment for 3 months..."
              required
            ></textarea>
          </div>
          <div v-if="disconnectError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
            {{ disconnectError }}
          </div>
        </div>

        <div class="flex gap-3">
          <button
            @click="confirmDisconnect"
            :disabled="disconnecting || !disconnectReason"
            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400"
          >
            {{ disconnecting ? 'Disconnecting...' : 'Confirm Disconnect' }}
          </button>
          <button
            @click="closeDisconnectModal"
            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Html5Qrcode } from 'html5-qrcode'

definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()
const router = useRouter()
const api = useApi()

const members = ref([])
const loading = ref(false)
const error = ref('')
const searchQuery = ref('')
const sortField = ref('member_id')
const sortOrder = ref('asc')
const authInitialized = ref(false)

// Disconnect modal states
const showDisconnectModal = ref(false)
const selectedMember = ref(null)
const disconnectReason = ref('')
const disconnecting = ref(false)
const disconnectError = ref('')
const showPrinterSetup = ref(false)

// Barcode scanner states
const showScanner = ref(false)
const scanError = ref('')
const scanSuccess = ref('')
let html5QrCode = null

let searchTimeout = null

const formatMoney = (amount) => {
  return Number(amount || 0).toFixed(2)
}

onBeforeMount(() => {
  if (process.client) {
    authStore.initAuth()
    authInitialized.value = true
  }
})

const loadMembers = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const params = new URLSearchParams({
      sort_field: sortField.value,
      sort_order: sortOrder.value,
      _t: Date.now() // Cache buster to ensure fresh data
    })
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }    members.value = await api(`/members?${params.toString()}`)
    
    // Optionally log member count for debugging
    // console.log('Members loaded:', members.value.length, 'members')
  } catch (err) {
    error.value = 'Failed to load members. Please try again.'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const searchMembers = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadMembers()
  }, 300)
}

const viewMember = (member) => {
  if (member.connection_status === 0) {
    router.push(`/collector/disconnected/${member.member_id}`)
  } else {
    router.push(`/collector/statement/${member.member_id}`)
  }
}

const openDisconnectModal = (member) => {
  selectedMember.value = member
  disconnectReason.value = ''
  disconnectError.value = ''
  showDisconnectModal.value = true
}

const closeDisconnectModal = () => {
  showDisconnectModal.value = false
  selectedMember.value = null
  disconnectReason.value = ''
  disconnectError.value = ''
}

const confirmDisconnect = async () => {
  if (!disconnectReason.value.trim()) {
    disconnectError.value = 'Please provide a reason for disconnection'
    return
  }

  disconnecting.value = true
  disconnectError.value = ''

  try {
    await api(`/members/${selectedMember.value.member_id}/disconnect`, {
      method: 'POST',
      body: {
        reason: disconnectReason.value
      }
    })

    await loadMembers()
    closeDisconnectModal()
    alert('Member has been disconnected successfully')
  } catch (err) {
    disconnectError.value = err.data?.message || 'Failed to disconnect member. Please try again.'
  } finally {
    disconnecting.value = false
  }
}

// Barcode Scanner Functions
const startScanner = async () => {
  scanError.value = ''
  scanSuccess.value = ''
  
  try {
    await nextTick()
    
    html5QrCode = new Html5Qrcode("barcode-scanner")
    
    const config = {
      fps: 10,
      qrbox: function(viewfinderWidth, viewfinderHeight) {
        // Calculate optimal square size based on container dimensions
        const minEdgePercentage = 0.7 // 70% of the smaller dimension
        const minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight)
        const qrboxSize = Math.floor(minEdgeSize * minEdgePercentage)
        return {
          width: qrboxSize,
          height: qrboxSize
        }
      },
      aspectRatio: 1.777778, // 16:9 aspect ratio
      disableFlip: false,
      videoConstraints: {
        facingMode: "environment" // Use back camera if available
      }
    }
    
    await html5QrCode.start(
      { facingMode: "environment" },
      config,
      onScanSuccess,
      onScanError
    )
  } catch (err) {
    console.error('Scanner error:', err)
    scanError.value = 'Failed to start camera. Please check camera permissions and try again.'
  }
}

const onScanSuccess = async (decodedText) => {
  console.log('Barcode scanned:', decodedText)
  
  try {
    // Stop scanner
    if (html5QrCode && html5QrCode.isScanning) {
      await html5QrCode.stop()
    }
    
    scanSuccess.value = 'Barcode detected! Redirecting...'
    
    // Find member by account number
    const member = await api(`/members/by-account/${decodedText}`)
    
    if (member) {
      // Close scanner modal
      closeScanner()
      
      // Redirect based on connection status
      if (member.connection_status === 0) {
        router.push(`/collector/disconnected/${member.member_id}`)
      } else {
        router.push(`/collector/statement/${member.member_id}`)
      }
    } else {
      scanError.value = 'Member not found with this account number'
      // Restart scanner after 2 seconds
      setTimeout(() => {
        scanError.value = ''
        startScanner()
      }, 2000)
    }
  } catch (err) {
    console.error('Error processing scan:', err)
    scanError.value = err.data?.message || 'Failed to find member'
    // Restart scanner after 2 seconds
    setTimeout(() => {
      scanError.value = ''
      startScanner()
    }, 2000)
  }
}

const onScanError = (errorMessage) => {
  // Ignore verbose scanning errors
  if (!errorMessage.includes('No MultiFormat Readers')) {
    console.warn('Scan error:', errorMessage)
  }
}

const closeScanner = async () => {
  try {
    if (html5QrCode && html5QrCode.isScanning) {
      await html5QrCode.stop()
    }
  } catch (err) {
    console.error('Error stopping scanner:', err)
  } finally {
    html5QrCode = null
    showScanner.value = false
    scanError.value = ''
    scanSuccess.value = ''
  }
}

// Watch for scanner modal opening
watch(showScanner, (newValue) => {
  if (newValue) {
    startScanner()
  }
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}

onMounted(() => {
  loadMembers()
  
  // Check for success messages from payment/reconnection
  const paymentSuccess = sessionStorage.getItem('paymentSuccess')
  const reconnectionSuccess = sessionStorage.getItem('reconnectionSuccess')
  const memberUpdateSuccess = sessionStorage.getItem('memberUpdateSuccess')
  
  if (paymentSuccess) {
    console.log('Payment successful, refreshing member list')
    sessionStorage.removeItem('paymentSuccess')
    // Show a brief notification that data is being refreshed
    const notificationElement = document.createElement('div')
    notificationElement.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50'
    notificationElement.textContent = '✓ Payment completed! Refreshing member list...'
    document.body.appendChild(notificationElement)
    
    // Refresh after a short delay and remove notification
    setTimeout(() => {
      loadMembers()
      document.body.removeChild(notificationElement)
    }, 1000)
  }
  
  if (reconnectionSuccess) {
    console.log('Reconnection successful, refreshing member list')
    sessionStorage.removeItem('reconnectionSuccess')
    const notificationElement = document.createElement('div')
    notificationElement.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50'
    notificationElement.textContent = '✓ Reconnection completed! Refreshing member list...'
    document.body.appendChild(notificationElement)
    
    setTimeout(() => {
      loadMembers()
      document.body.removeChild(notificationElement)
    }, 1000)
  }
  
  if (memberUpdateSuccess) {
    console.log('Member update successful, refreshing member list')
    sessionStorage.removeItem('memberUpdateSuccess')
    const notificationElement = document.createElement('div')
    notificationElement.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50'
    notificationElement.textContent = '✓ Member updated! Refreshing member list...'
    document.body.appendChild(notificationElement)
    
    setTimeout(() => {
      loadMembers()
      document.body.removeChild(notificationElement)
    }, 1000)
  }
})

// Watch for route changes to refresh data when returning to dashboard
watch(() => router.currentRoute.value.path, (newPath) => {
  if (newPath === '/collector/dashboard') {
    console.log('Returned to dashboard, refreshing member list')
    loadMembers()
  }
})

onUnmounted(() => {
  closeScanner()
})
</script>

<style scoped>
/* Ensure the barcode scanner container has proper styling */
#barcode-scanner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Style the scanner video element */
#barcode-scanner video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 0.5rem;
}

/* Ensure the scanning overlay is visible */
#barcode-scanner canvas {
  position: absolute !important;
  top: 0;
  left: 0;
  width: 100% !important;
  height: 100% !important;
  z-index: 10;
}
</style>