<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow-md">
      <div class="container mx-auto px-4 py-4">
        <button
          @click="router.push(`/collector/statement/${route.params.id}`)"
          class="text-blue-600 hover:text-blue-800 mb-2"
        >
          ← Back to Statement
        </button>
        <h1 class="text-2xl font-bold text-gray-800">Payment Information</h1>
      </div>
    </header>

    <main class="container mx-auto px-4 py-8">
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-600">Loading payment information...</p>
      </div>

      <div v-else-if="!paymentProcessed" class="max-w-2xl mx-auto">        
        <!-- OR Warning Modal -->
        <div v-if="showOrWarning" class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>            <div class="flex-1">
              <p class="font-medium text-yellow-800">Low INVOICE Number Stock</p>
              <p class="text-sm text-yellow-700 mt-1">
                Only {{ orAvailableCount }} INVOICE numbers remaining. Please inform the administrator to add more INVOICE numbers soon.
              </p>
            </div>
            <button @click="showOrWarning = false" class="text-yellow-600 hover:text-yellow-800">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>        </div>

        <!-- No OR Numbers Warning -->
        <div v-if="orAvailableCount === 0" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>            <div class="flex-1">
              <p class="font-medium text-red-800">No INVOICE Numbers Available</p>
              <p class="text-sm text-red-700 mt-1">
                Payment can still be processed, but receipts cannot be printed until more INVOICE numbers are added.
              </p>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 space-y-6">
          <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Payment Details</h2>            <!-- Payment Status Indicator -->
            <div v-if="memberData?.is_paid" class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
              <p class="font-medium text-green-800">✓ Payment Status: PAID</p>
              <p class="text-sm text-green-600">Current bill has been paid in full.</p>
            </div>

            <div v-else class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
              <p class="font-medium text-red-800">⚠ Payment Status: UNPAID</p>
              <p class="text-sm text-red-600">Current bill is pending payment.</p>
            </div>
            
            <!-- Balance Payment Indicator -->
            <div v-if="billDetails?.is_balance_payment" class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-4">
              <p class="font-medium text-orange-800">💰 Balance Payment</p>
              <p class="text-sm text-orange-600">You have already made a partial payment this billing period. Only the remaining balance needs to be paid.</p>
            </div>            <div v-if="isDefectiveMeter" class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
              <p class="font-medium text-yellow-800">⚠️ Defective Meter Payment</p>
              <p class="text-sm text-yellow-600 mb-2">This payment is for a member with a replaced or defective water meter.</p>
              <div class="text-xs text-yellow-700 mt-2 p-2 bg-yellow-100 rounded">
                <strong>Important:</strong> Water consumption data will be reset to zero:
                <ul class="list-disc list-inside mt-1">
                  <li>Previous CUM consumption → 0</li>
                  <li>Present CUM consumption → 0</li>
                  <li>Previous meter reading → 0</li>
                  <li>Present meter reading → 0</li>
                </ul>
              </div>
            </div><div v-if="isDisconnectedBalancePayment" class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
              <p class="font-medium text-amber-800">Disconnected Member - Balance Payment Only</p>
              <p class="text-sm text-amber-600">Paying outstanding balance without reconnection. Member remains disconnected.</p>
            </div>

            <!-- Shared Meter Information -->
            <div v-if="sharedMeterInfo && sharedMeterInfo.length > 1" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
              <p class="font-medium text-blue-800">🔗 Shared Meter: {{ memberData?.meter_no }}</p>
              <p class="text-sm text-blue-600 mb-2">Payment will affect all {{ sharedMeterInfo.length }} members sharing this meter:</p>
              <div class="text-sm space-y-1">
                <div v-for="member in sharedMeterInfo" :key="member.account_no" 
                     :class="member.account_no === memberData?.account_no ? 'font-semibold text-blue-800' : 'text-blue-600'">
                  • {{ member.account_no }} - {{ member.full_name }} 
                  <span v-if="member.is_paid" class="text-green-600">(✓ Paid)</span>
                  <span v-else class="text-red-600">(⚠ Unpaid)</span>
                </div>
              </div>
            </div>            <!-- Previous Balance (only show for full bills, not balance payments) -->
            <div v-if="!billDetails?.is_balance_payment && memberData?.prev_balance > 0" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
              <p class="text-sm text-gray-700">Previous Balance</p>
              <p class="text-xl font-bold text-red-600">₱{{ formatMoney(memberData?.prev_balance) }}</p>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
              <p class="text-sm text-gray-700">
                {{ billDetails?.is_balance_payment ? 'Balance to Pay' : 'Total Bill' }}
              </p>
              <p class="text-3xl font-bold text-blue-600">₱{{ formatMoney(totalBill) }}</p>
              <div v-if="billDetails?.is_balance_payment" class="text-sm text-orange-600 mt-2">
                Only the remaining balance from your partial payment
              </div>
            </div>

            <!-- Bill Breakdown (only show for new bills, not balance payments) -->
            <div v-if="!billDetails?.is_balance_payment && billDetails" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
              <h4 class="font-semibold text-gray-800 mb-3">Bill Breakdown</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>Minimum Amount:</span>
                  <span>₱{{ formatMoney(billDetails.minimum_amount) }}</span>
                </div>
                <div v-if="billDetails.excess_charge > 0" class="flex justify-between">
                  <span>Excess Usage ({{ billDetails.excess_minimum_CUM }} CUM):</span>
                  <span>₱{{ formatMoney(billDetails.excess_charge) }}</span>
                </div>                <div v-if="billDetails.vat > 0" class="flex justify-between">
                  <span>Miscellaneous:</span>
                  <span>₱{{ formatMoney(billDetails.vat) }}</span>
                </div>
                <div v-if="billDetails.electricity_consumption > 0" class="flex justify-between">
                  <span>Electricity:</span>
                  <span>₱{{ formatMoney(billDetails.electricity_consumption) }}</span>
                </div>                <div v-if="billDetails.generator_consumption > 0" class="flex justify-between">
                  <span>Generator:</span>
                  <span>₱{{ formatMoney(billDetails.generator_consumption) }}</span>
                </div>
                <div v-if="billDetails.damage_charges > 0" class="flex justify-between">
                  <span>Additional Charges:</span>
                  <span>₱{{ formatMoney(billDetails.damage_charges) }}</span>
                </div>
                <div v-if="billDetails.prev_balance > 0" class="flex justify-between font-medium text-red-600">
                  <span>Previous Balance:</span>
                  <span>₱{{ formatMoney(billDetails.prev_balance) }}</span>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Cash Amount
              </label>
              <input
                v-model.number="cash"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Enter cash amount"
              />
            </div>

            <div v-if="cash > 0" class="bg-gray-50 p-4 rounded-lg">              <div class="flex justify-between">
                <span class="text-gray-700">Change:</span>
                <span :class="change >= 0 ? 'text-green-600' : 'text-red-600'" class="font-bold text-xl">
                  ₱{{ formatMoney(Math.abs(change)) }}
                </span>
              </div>              <p v-if="change < 0 && isDefectiveMeter" class="text-yellow-600 text-sm mt-2">
                ⚠️ Partial payment - Defective meter (ALL consumption data will be reset to 0).
              </p>
              <p v-if="change < 0 && !isDefectiveMeter" class="text-blue-600 text-sm mt-2">
                💰 Partial payment - Remaining balance: ₱{{ formatMoney(Math.abs(change)) }}
              </p>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
              {{ error }}
            </div>
          </div>

          <div class="flex gap-4">            <button
              @click="attemptProcess()"
              :disabled="processing || cash <= 0"
              class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400"
            >
              {{ processing ? 'Processing...' : 'Process Payment' }}
            </button>
            <button
              @click="router.push(`/collector/statement/${route.params.id}`)"
              class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- After Payment Success -->
      <div v-else class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold text-green-600 mb-4 text-center">
            ✓ Payment Processed Successfully
          </h2>            <div class="space-y-4 mb-6">
            <div class="flex justify-between">
              <span class="text-gray-700">Invoice Number:</span>
              <span class="font-semibold">{{ paymentData?.or_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-700">Amount Paid:</span>
              <span class="font-semibold">₱{{ formatMoney(cash) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-700">Change:</span>
              <span class="font-semibold text-green-600">₱{{ formatMoney(Math.max(0, cash - totalBill)) }}</span>
            </div>
            
            <!-- Shared Meter Payment Results -->
            <div v-if="paymentData?.shared_meter_info && paymentData.shared_meter_info.total_members_affected > 1" 
                 class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
              <p class="font-semibold text-blue-800 mb-2">
                🔗 Shared Meter Payment Updated ({{ paymentData.shared_meter_info.total_members_affected }} members)
              </p>
              <div class="text-sm space-y-1">
                <div v-for="member in paymentData.shared_meter_info.affected_members" :key="member.account_no"
                     :class="member.account_no === memberData?.account_no ? 'font-semibold text-blue-800' : 'text-blue-600'">
                  • {{ member.account_no }} - {{ member.full_name }}
                  <span v-if="member.is_paid" class="text-green-600">(✓ Now Paid)</span>
                  <span v-else class="text-orange-600">(Partial Payment - ₱{{ formatMoney(member.prev_balance) }} remaining)</span>
                </div>
              </div>
            </div>
          </div><!-- Bluetooth Printer Component -->
          <div class="mb-6">
            <BluetoothThermalPrinter 
              ref="bluetoothPrinter"
              @printed="onPrinted"
            />
          </div>          <!-- Receipt Printing Section -->
          <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h4 class="font-semibold text-gray-800 mb-3">Receipt Printing</h4>
            
            <!-- No OR Warning for Printing -->
            <div v-if="!canPrintReceipt && orWarningMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-3">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-medium">{{ orWarningMessage }}</span>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
              <button 
                @click="printMemberCopy" 
                :disabled="printingMember || !bluetoothPrinter?.isConnected || !canPrintReceipt"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg disabled:bg-gray-400"
              >
                {{ printingMember ? 'Printing...' : 'Print Consumer\'s Copy' }}
              </button>
              
              <button 
                @click="printOfficeCopy" 
                :disabled="printingOffice || !bluetoothPrinter?.isConnected || !canPrintReceipt"
                :class="memberCopyPrinted ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-600 hover:bg-orange-700'"
                class="text-white px-4 py-2 rounded-lg disabled:bg-gray-400"
              >
                {{ printingOffice ? 'Printing...' : 'Print Office Copy' }}
              </button>
              
              <button 
                @click="printBothCopies" 
                v-if="bothCopiesPrinted"
                :disabled="printingMember || printingOffice || !canPrintReceipt"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg disabled:bg-gray-400"
              >
                🔄 Reprint Both
              </button>
            </div>
            
            <div class="text-sm text-gray-600 space-y-1">
              <div v-if="memberCopyPrinted" class="text-green-600">✓ Consumer's copy printed</div>
              <div v-if="officeCopyPrinted" class="text-green-600">✓ Office copy printed</div>
              <div v-if="!bluetoothPrinter?.isConnected" class="text-red-600">⚠ Please connect to Bluetooth printer first</div>              <div v-if="!canPrintReceipt" class="text-red-600">⚠ Printing disabled - No INVOICE numbers available</div>
            </div>
          </div>

          <div class="flex gap-4">
            <button
              @click="router.push('/collector/dashboard')"
              class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Back to Dashboard
            </button>
          </div>          <!-- Receipt Preview -->
          <div class="mt-6 bg-gray-50 rounded-lg p-4 font-mono text-xs">
            <div class="text-center mb-2">
              <div class="font-bold">CHARMAFA</div>
              <div>Water Association</div>
              <div v-if="memberCopyPrinted || officeCopyPrinted" class="text-sm font-bold text-blue-600">
                RECEIPT PREVIEW
              </div>
              <div class="border-b border-dashed border-gray-400 my-2"></div>
            </div>            <div class="space-y-1">
            <div>Account: {{ thermalReceiptData?.member?.account_no }}</div>
            <div>Name: {{ thermalReceiptData?.member?.name }}</div>
            <div>INVOICE: {{ thermalReceiptData?.or_number }}</div>
            <div v-if="thermalReceiptData?.reference">Ref: {{ thermalReceiptData?.reference }}</div>
            <div v-if="thermalReceiptData?.billing_date">Billing Date: {{ formatDate(thermalReceiptData?.billing_date) }}</div>
            <div v-if="thermalReceiptData?.meter_reading">Meter: {{ thermalReceiptData?.meter_reading }}</div>
            <div v-if="thermalReceiptData?.cum_usage">Usage: {{ thermalReceiptData?.cum_usage }} CUM</div>
            <div class="border-b border-dashed border-gray-400 my-2"></div>
            <div>Total Bill: ₱{{ formatMoney(thermalReceiptData?.total_bill) }}</div>
            <div>Cash: ₱{{ formatMoney(thermalReceiptData?.cash) }}</div>
            <div class="font-bold">Change: ₱{{ formatMoney(thermalReceiptData?.change) }}</div>
          </div>
          </div>
        </div>
      </div>    </main>
    <DefectMeterModal
      :show="showDefectModal"
      @response="onDefectModalResponse"
    />
  </div>
</template>

<script setup>
import { useNotifications } from '~/composables/useNotifications'
import DefectMeterModal from '~/components/DefectMeterModal.vue'

definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const router = useRouter()
const api = useApi()
const authStore = useAuthStore()
const bluetoothPrinter = ref(null)

const loading = ref(true)
const processing = ref(false)
const error = ref('')
const cash = ref(0)
const totalBill = ref(0)
const paymentProcessed = ref(false)
const paymentData = ref(null)
const memberData = ref(null)
const billDetails = ref(null)
const isDefectiveMeter = ref(route.query.defectiveMeter === 'true')
const isDisconnectedBalancePayment = ref(route.query.disconnected === 'true' && route.query.balanceOnly === 'true')
const thermalReceiptData = ref(null)
const sharedMeterInfo = ref([])
const orAvailableCount = ref(null)
const showOrWarning = ref(false)
const showDefectModal = ref(false)
const canPrintReceipt = ref(true)
const orWarningMessage = ref('')

// Receipt printing states
const memberCopyPrinted = ref(false)
const officeCopyPrinted = ref(false)
const printingMember = ref(false)
const printingOffice = ref(false)

const change = computed(() => Number(cash.value || 0) - Number(totalBill.value || 0))

const bothCopiesPrinted = computed(() => 
  memberCopyPrinted.value && officeCopyPrinted.value
)

const formatMoney = (amount) => {
  return Number(amount || 0).toFixed(2)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const loadPaymentInfo = async () => {
  loading.value = true
  error.value = ''
  
  try {    const data = await api(`/members/${route.params.id}`)
    
    memberData.value = data.member
    billDetails.value = data.bill_details
    
    if (isDisconnectedBalancePayment.value) {
      // For disconnected balance payment, only pay the previous balance
      totalBill.value = Number(data.member.prev_balance || 0)
    } else {
      totalBill.value = Number(data.bill_details.total_bill || 0)
    }
      // Check OR number availability
    try {
      const orCountData = await api('/or-booklets/available-count')
      orAvailableCount.value = orCountData.available_count
        if (orAvailableCount.value === 0) {
        console.warn('No INVOICE numbers available - receipts cannot be printed')
        // Don't set error here, allow payment processing
      } else if (orAvailableCount.value <= 5) {
        showOrWarning.value = true
      }
    } catch (err) {
      console.error('Failed to check OR availability:', err)
    }
    
    // Load shared meter information
    if (data.member.meter_no) {
      try {
        const sharedMeterResponse = await api(`/members/shared-meter/${data.member.meter_no}`)
        sharedMeterInfo.value = sharedMeterResponse.shared_members || []
      } catch (err) {
        console.warn('Could not load shared meter info:', err)
        sharedMeterInfo.value = []
      }
    }
  } catch (err) {
    error.value = 'Failed to load payment information.'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const attemptProcess = () => {
  // If defective meter flag, show confirmation modal before proceeding
  if (isDefectiveMeter.value) {
    showDefectModal.value = true
    return
  }
  processPayment()
}

const onDefectModalResponse = (response) => {
  showDefectModal.value = false
  if (response === null) return // cancelled
  
  // Handle new response format with type and isDefective properties
  if (response.isDefective) {
    // Confirmed defective meter => proceed with consumption reset
    isDefectiveMeter.value = true
    processPayment()
  } else {
    // Regular payment => proceed without consumption reset
    isDefectiveMeter.value = false
    processPayment()
  }
}

const processPayment = async () => {
  // Partial payments are allowed for all payment types
  // Only check for minimum amount (must be > 0)
  
  if (isDisconnectedBalancePayment.value && cash.value < totalBill.value) {
    error.value = 'Insufficient cash amount to pay the balance.'
    return
  }

  if (cash.value <= 0) {
    error.value = 'Cash amount must be greater than 0.'
    return
  }

  processing.value = true
  error.value = ''
  const { showSuccess } = useNotifications()
  
  try {
    let result
      if (isDisconnectedBalancePayment.value) {
      // Use the specific endpoint for disconnected balance payment
      const response = await api(`/reconnection/${route.params.id}/pay-balance`, {
        method: 'POST',
        body: {
          cash: cash.value
        }
      })
      
      // Transform response to match payment structure
      result = {
        message: 'Payment processed successfully',
        member: response.member,
        payment: response.payment || {
          or_number: `BAL-${response.member.account_no}-${Date.now()}`
        },
        total_bill: response.amount_paid,
        cash: response.cash,
        change: response.change,
        remaining_balance: response.remaining_balance
      }
    }else {
      // Regular payment processing
      result = await api(`/payments/${route.params.id}`, {
        method: 'POST',
        body: {
          cash: Number(cash.value.toFixed(2)),
          total_bill: Number(totalBill.value.toFixed(2)),
          is_defective_meter: Boolean(isDefectiveMeter.value),
          is_partial_payment: Boolean(cash.value < totalBill.value)
        }
      })
    }      // Process payment success
    if (result.message === 'Payment processed successfully') {
      paymentData.value = result
      
      // Handle OR number availability
      if (typeof result.can_print_receipt !== 'undefined') {
        canPrintReceipt.value = result.can_print_receipt
        orWarningMessage.value = result.or_warning || ''
      }
      
      // Update local member data with new payment status
      if (memberData.value) {
        memberData.value.is_paid = result.remaining_balance <= 0
        memberData.value.prev_balance = result.remaining_balance
      }
      
      // Update shared meter info if available
      if (result.shared_meter_info) {
        sharedMeterInfo.value = result.shared_meter_info.affected_members || sharedMeterInfo.value
      }
        // Prepare thermal receipt data
      thermalReceiptData.value = {
        member: {
          account_no: result.member.account_no,
          name: `${result.member.fname} ${result.member.lname}`,
          meter_no: result.member.meter_no,
          address: `${result.member.purok || ''} ${result.member.barangay || 'Charito'}, ${result.member.municipality || 'Bayugan City'}`.trim()
        },
        or_number: result.payment.or_number || result.payment.receipt_number,
        billing_date: result.payment.billing_date,
        reference: result.payment.reference,
        meter_reading: result.payment.meter_reading,
        cum_usage: result.payment.cum_usage,
        prev_balance: result.payment.balance || 0,
        water_charges: result.payment.bill_amount || result.total_bill,
        penalty: 0,
        other_fees: 0,
        other_fees_description: '',
        amount_paid: result.payment.amount_paid || result.cash,
        total_bill: result.payment.bill_amount || result.total_bill,
        cash: result.cash,
        change: result.change,
        remaining_balance: result.remaining_balance,
        is_balance_payment: isDisconnectedBalancePayment.value,
        shared_meter_info: result.shared_meter_info,
        processed_by: `${authStore.user?.fname} ${authStore.user?.lname}`
      }
      
      paymentProcessed.value = true
      showSuccess('Payment processed successfully')
      
      // Set success flag for dashboard refresh
      sessionStorage.setItem('paymentSuccess', 'true')
    } else {
      error.value = result.message || 'Payment processing failed. Please try again.'
    }  } catch (err) {
    console.error('Payment error:', err)
    
    // Handle shared meter already paid error
    if (err.response?.status === 400 && err.response?.data?.shared_meter_no) {
      const errorData = err.response.data
      error.value = `${errorData.message}\nMeter ${errorData.shared_meter_no} was already paid by ${errorData.paid_by?.full_name} (${errorData.paid_by?.account_no})`
      
      // Show shared member details
      if (errorData.shared_members) {
        console.log('Shared meter members:', errorData.shared_members)
        sharedMeterInfo.value = errorData.shared_members
      }
    } else {
      error.value = err.response?.data?.message || 'Payment processing failed. Please try again.'
    }
  } finally {
    processing.value = false
  }
}

const printMemberCopy = async () => {
  if (!canPrintReceipt.value) {
    error.value = 'Cannot print receipt - No INVOICE numbers available'
    return
  }
  
  if (!bluetoothPrinter.value?.isConnected) {
    error.value = 'Please connect to Bluetooth printer first'
    return
  }

  printingMember.value = true
  try {
    const receiptData = {
      ...thermalReceiptData.value,
      copyType: 'CONSUMER\'S COPY',
      copyNote: 'Please keep this receipt for your records'
    }
    
    const success = await bluetoothPrinter.value.printReceipt(receiptData)
    if (success) {
      memberCopyPrinted.value = true
      const { showSuccess } = useNotifications()
      showSuccess('Consumer\'s copy printed successfully!')
    } else {
      error.value = 'Failed to print consumer\'s copy'
    }
  } catch (err) {
    console.error('Print error:', err)
    error.value = 'Failed to print consumer\'s copy'
  } finally {
    printingMember.value = false
  }
}

const printOfficeCopy = async () => {
  if (!canPrintReceipt.value) {
    error.value = 'Cannot print receipt - No INVOICE numbers available'
    return
  }
  
  if (!bluetoothPrinter.value?.isConnected) {
    error.value = 'Please connect to Bluetooth printer first'
    return
  }

  printingOffice.value = true
  try {
    const receiptData = {
      ...thermalReceiptData.value,
      copyType: 'OFFICE COPY',
      copyNote: 'For office records and filing'
    }
    
    const success = await bluetoothPrinter.value.printReceipt(receiptData)
    if (success) {
      officeCopyPrinted.value = true
      const { showSuccess } = useNotifications()
      showSuccess('Office copy printed successfully!')
    } else {
      error.value = 'Failed to print office copy'
    }
  } catch (err) {
    console.error('Print error:', err)
    error.value = 'Failed to print office copy'
  } finally {
    printingOffice.value = false
  }
}

const printBothCopies = async () => {
  await printMemberCopy()
  
  // Wait 2 seconds for member to cut the first receipt
  setTimeout(async () => {
    await printOfficeCopy()
  }, 2000)
}

const printBluetoothReceipt = async () => {
  // Legacy function - redirect to consumer's copy
  await printMemberCopy()
}

const onPrinted = () => {
  const { showSuccess } = useNotifications()
  showSuccess('Receipt printed successfully!')
}

onMounted(() => {
  loadPaymentInfo()
})
</script>