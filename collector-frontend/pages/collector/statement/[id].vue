<!-- collector/statement/[id].vue -->

<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow-md">
      <div class="container mx-auto px-4 py-4">
        <button
          @click="router.push('/collector/dashboard')"
          class="text-blue-600 hover:text-blue-800 mb-2"
        >
          ← Back to Dashboard
        </button>
        <h1 class="text-2xl font-bold text-gray-800">Statement of Account</h1>
      </div>
    </header>

    <main class="container mx-auto px-4 py-8">
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-600">Loading member details...</p>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg">
        {{ error }}
      </div>

      <div v-else-if="memberData" class="space-y-6">        <!-- Member Profile -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-bold text-gray-800">Member Profile</h2>
            <div v-if="isDisconnected" class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
              DISCONNECTED
            </div>
          </div>          <div v-if="isDisconnected" class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
            <p class="text-amber-800 font-semibold">⚠️ Disconnected Member Payment</p>
            <p class="text-amber-700 text-sm mt-1">
              This member is disconnected. You can pay the outstanding balance without reconnecting.
            </p>
            <p class="text-amber-700 text-sm mt-1">
              <strong>Reconnection Rule:</strong> Balance must be paid first before reconnection is allowed.
            </p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600">Account Name</p>
              <p class="font-semibold text-gray-900">
                {{ memberData.member.fname }} {{ memberData.member.mname }} {{ memberData.member.lname }} {{ memberData.member.suffix }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Account Number</p>
              <p class="font-semibold text-gray-900">{{ memberData.member.account_no }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Meter Number</p>
              <p class="font-semibold text-gray-900">{{ memberData.member.meter_no }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Address</p>
              <p class="font-semibold text-gray-900">
                {{ memberData.member.barangay }}, {{ memberData.member.municipality }}
              </p>
            </div>
          </div>
        </div>        <!-- Bill Details -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-4">Billing Details</h2>          <!-- Balance Payment Notice -->
          <div v-if="bill?.is_balance_payment" class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
            <p class="font-medium text-orange-800">💰 Balance Payment Only</p>
            <p class="text-sm text-orange-600 mb-2">You have already made a partial payment or there's no new meter reading. Only the remaining balance needs to be paid.</p>
            
            <!-- Transparency: Show what the balance consists of -->
            <div class="bg-white rounded p-3 border mt-3">
              <p class="text-xs text-gray-600 mb-2">Current status (for transparency):</p>
              <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Previous unpaid amount:</span>
                  <span>₱{{ formatMoney(bill.prev_balance) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Additional charges (current):</span>
                  <span :class="(bill.damage_charges || 0) > 0 ? 'text-orange-600 font-medium' : ''">
                    ₱{{ formatMoney(bill.damage_charges || 0) }}
                    <span v-if="(bill.damage_charges || 0) > 0" class="text-xs text-orange-500 ml-1">(will be added to next bill)</span>
                  </span>
                </div>
                <div class="border-t pt-2 flex justify-between items-center">
                  <span class="text-gray-700 font-medium">Amount to Pay Now:</span>
                  <span class="text-xl font-bold text-red-600">₱{{ formatMoney(bill.total_bill) }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Full Bill Breakdown (only show for new bills) -->
          <div v-else class="space-y-3">
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Minimum Amount per Month (FREE 5 CUM/Month)</span>
              <span class="font-semibold">₱{{ formatMoney(bill.minimum_amount) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Excess CUM ({{ bill.excess_minimum_CUM }} CUM)</span>
              <span class="font-semibold">₱{{ formatMoney(bill.excess_charge) }}</span>
            </div>
            
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Connector Damage with Unknown Person</span>
              <span class="font-semibold">₱{{ formatMoney(bill.connector_damage) }}</span>
            </div>            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Miscellaneous Charges</span>
              <span class="font-semibold">₱{{ formatMoney(bill.vat) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Generator Consumption (DIESEL)</span>
              <span class="font-semibold">₱{{ formatMoney(bill.generator_consumption) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Electricity Consumption</span>
              <span class="font-semibold">₱{{ formatMoney(bill.electricity_consumption) }}</span>
            </div>            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Damages</span>
              <span class="font-semibold">₱{{ formatMoney(bill.damages) }}</span>
            </div>            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">
                Additional Charges
                <span v-if="(bill.damage_charges || 0) > 0" class="text-xs text-orange-600 ml-1">(Applied by President)</span>
              </span>
              <span class="font-semibold" :class="(bill.damage_charges || 0) > 0 ? 'text-orange-600' : ''">
                ₱{{ formatMoney(bill.damage_charges || 0) }}
              </span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Others</span>
              <span class="font-semibold">₱{{ formatMoney(bill.others) }}</span>
            </div>
            <!-- <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Business Permit</span>
              <span class="font-semibold">₱{{ bill.business_permit.toFixed(2) }}</span>
            </div> -->
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Penalty</span>
              <span class="font-semibold">₱{{ formatMoney(bill.penalty) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Charges</span>
              <span class="font-semibold">₱{{ formatMoney(bill.charges) }}</span>
            </div>            <div class="flex justify-between py-2 border-b">
              <span class="text-gray-700">Previous Balance</span>
              <span class="font-semibold text-red-600">₱{{ formatMoney(bill.prev_balance) }}</span>
            </div>
            
            <!-- Total summary only for full bills -->
            <div class="flex justify-between py-3 border-t-2 border-gray-800">
              <span class="text-xl font-bold text-gray-900">TOTAL BILL</span>
              <span class="text-xl font-bold text-blue-600">₱{{ formatMoney(bill.total_bill) }}</span>
            </div>
          </div>
            <!-- Total summary for balance payments (separate display) -->
          <div v-if="bill?.is_balance_payment" class="mt-4 pt-4 border-t-2 border-orange-300">
            <div class="flex justify-between items-center">
              <span class="text-xl font-bold text-gray-900">BALANCE TO PAY</span>
              <span class="text-xl font-bold text-orange-600">₱{{ formatMoney(bill.total_bill) }}</span>
            </div>
          </div>
        </div>        <!-- Payment Status -->
        <div v-if="memberData.member.is_paid && memberData.member.prev_balance <= 0" class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
          <p class="font-medium text-green-800">✓ Payment Status: FULLY PAID</p>
          <p class="text-sm text-green-600">Current bill has been paid in full.</p>
        </div>

        <div v-else-if="memberData.member.is_paid && memberData.member.prev_balance > 0" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
          <p class="font-medium text-blue-800">Payment Status: PARTIALLY PAID</p>
        </div>

        <div v-else class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
          <p class="font-medium text-red-800">⚠ Payment Status: UNPAID</p>
          <p class="text-sm text-red-600">Current bill is pending payment.</p>
        </div><!-- Action Buttons -->
        <div class="space-y-4">
          <!-- Show "Already Paid" message only if member has paid AND has no remaining balance -->
          <div v-if="memberData.member.is_paid && memberData.member.prev_balance <= 0" class="bg-green-50 border border-green-200 p-4 rounded-lg text-center">
            <p class="text-green-800 font-semibold">✓ This bill has been paid in full</p>
            <p class="text-green-600 text-sm mt-1">No payment is required at this time.</p>
            <button
              @click="router.push('/collector/dashboard')"
              class="mt-3 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors"
            >
              Back to Dashboard
            </button>
          </div>

          <!-- Show balance payment option if member is paid but has remaining balance -->
          <div v-else-if="memberData.member.is_paid && memberData.member.prev_balance > 0 && !isDisconnected" class="space-y-3">
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg text-center">
              <p class="text-blue-800 font-semibold">💰 Payment Available</p>
              <p class="text-blue-600 text-sm mt-1">You can make additional payments on the remaining balance.</p>
            </div>
            <button
              @click="showDefectMeterModal = true"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Pay Balance (₱{{ formatMoney(memberData.member.prev_balance) }})
            </button>
            <button
              @click="router.push('/collector/dashboard')"
              class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Back to Dashboard
            </button>
          </div>

          <!-- Payment buttons for unpaid bills -->
          <div v-else-if="!memberData.member.is_paid && !isDisconnected" class="flex gap-4">
            <button
              @click="showDefectMeterModal = true"
              class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Proceed to Payment
            </button>
            <button
              @click="router.push('/collector/dashboard')"
              class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
          <div v-else-if="!memberData.member.is_paid" class="space-y-3">
            <button
              v-if="bill.prev_balance > 0"
              @click="payBalanceOnly"
              class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Pay Balance Only (₱{{ formatMoney(bill.prev_balance) }})
            </button>
              <button
              @click="goToReconnection"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              <span v-if="bill.prev_balance > 0">
                Reconnection (Pay Balance First: ₱{{ formatMoney(bill.prev_balance) }})
              </span>
              <span v-else>
                Reconnect & Pay Fee (₱{{ formatMoney(reconnectionFee) }})
              </span>
            </button>
            
            <button
              @click="router.push('/collector/dashboard')"
              class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Back to Dashboard
            </button>
          </div>
        </div>
      </div>
    </main>
    
    <DefectMeterModal
      :show="showDefectMeterModal"
      @response="handleDefectMeterResponse"
    />
  </div>
</template>

<script setup>
import DefectMeterModal from '~/components/DefectMeterModal.vue'

definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const router = useRouter()
const api = useApi()

const memberData = ref(null)
const loading = ref(false)
const error = ref('')
const showDefectMeterModal = ref(false)
const reconnectionFee = ref(100)

const isDisconnected = computed(() => {
  return route.query.disconnected === 'true' || memberData.value?.member?.connection_status === 0
})

const bill = computed(() => memberData.value?.bill_details || {
  excess_minimum_CUM: 0,
  excess_charge: 0,
  minimum_amount: 0,
  electricity_consumption: 0,
  connector_damage: 0,
  generator_consumption: 0,
  damages: 0,
  others: 0,
  business_permit: 0,
  penalty: 0,
  charges: 0,
  vat: 0,
  prev_balance: 0,
  total_bill: 0,
  is_balance_payment: false
})

const formatMoney = (amount) => {
  return Number(amount || 0).toFixed(2)
}

const loadMemberDetails = async () => {
  loading.value = true
  error.value = ''
  
  try {
    memberData.value = await api(`/members/${route.params.id}`)
  } catch (err) {
    error.value = 'Failed to load member details. Please try again.'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const proceed = () => {
  router.push(`/collector/payment/${route.params.id}`)
}

const handleDefectMeterResponse = (response) => {
  showDefectMeterModal.value = false
  if (response !== null) { // Only redirect if a choice was made (not closed)
    // Handle new response format with type and isDefective properties
    const isDefective = response.isDefective || false
    router.push(`/collector/payment/${route.params.id}?defectiveMeter=${isDefective}`)
  }
}

const payBalanceOnly = () => {
  router.push(`/collector/payment/${route.params.id}?balanceOnly=true&disconnected=true`)
}

const goToReconnection = () => {
  router.push(`/collector/reconnection/${route.params.id}`)
}

onMounted(() => {
  loadMemberDetails()
})
</script>