<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full">
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-600">Loading member information...</p>
      </div>

      <div v-else-if="error" class="text-center">
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-4">
          {{ error }}
        </div>
        <button
          @click="router.push('/collector/dashboard')"
          class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-6 rounded-lg"
        >
          Back to Dashboard
        </button>
      </div>

      <div v-else-if="member" class="text-center">
        <div class="mb-6">
          <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Connection Disconnected</h2>
          
          <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 text-left">
            <p class="text-gray-700">
              <span class="font-semibold">{{ member.fname }} {{ member.mname }} {{ member.lname }} {{ member.suffix }}</span>
              has been disconnected.
            </p>
            <p class="text-sm text-gray-600 mt-2">
              Account No: <span class="font-semibold">{{ member.account_no }}</span>
            </p>            <p class="text-sm text-gray-600">
              Previous Balance: <span class="font-semibold text-red-600">₱{{ Number(member.prev_balance || 0).toFixed(2) }}</span>
            </p>
            
            <!-- Disconnection Reason -->
            <div v-if="disconnectionReason" class="mt-3 pt-3 border-t border-yellow-300">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Reason for Disconnection:</p>
              <p class="text-sm text-gray-800 italic">"{{ disconnectionReason }}"</p>
              <p v-if="disconnectedAt" class="text-xs text-gray-500 mt-1">
                Disconnected on: {{ formatDate(disconnectedAt) }}
              </p>
            </div>
          </div>
        </div>        <div class="space-y-3">          <button
            @click="router.push(`/collector/reconnection/${member.member_id}`)"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            Reconnect & Pay (₱{{ Number(reconnectionFee || 0).toFixed(2) }})
          </button>
          
          <button
            v-if="member.prev_balance > 0"
            @click="payBalanceOnly"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            Pay Balance Only (₱{{ Number(member.prev_balance || 0).toFixed(2) }})
          </button>          <div v-if="member.prev_balance > 0" class="bg-amber-50 border-l-4 border-amber-500 p-3">
            <p class="text-xs text-amber-700">
              <strong>Note:</strong> You can pay your balance without reconnecting. 
              Reconnection will require an additional ₱{{ Number(reconnectionFee || 0).toFixed(2) }} fee.
            </p>
          </div>
          
          <button
            @click="router.push('/collector/dashboard')"
            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            Back to Dashboard
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const router = useRouter()
const api = useApi()

const member = ref(null)
const loading = ref(false)
const error = ref('')
const disconnectionReason = ref('')
const disconnectedAt = ref(null)
const reconnectionFee = ref(100) // Default reconnection fee

const loadMember = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const data = await api(`/reconnection/${route.params.id}`)
    member.value = data.member
    disconnectionReason.value = data.disconnection_reason
    disconnectedAt.value = data.disconnected_at
    reconnectionFee.value = data.reconnection_fee || 100
  } catch (err) {
    error.value = 'Failed to load member information.'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const payBalanceOnly = () => {
  // Redirect to statement page for balance payment only
  router.push(`/collector/statement/${member.value.member_id}?disconnected=true`)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadMember()
})
</script>