<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-green-700 text-white shadow-md">
      <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-4">
            <button @click="goBack" class="text-white hover:text-green-200 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center overflow-hidden">
              <img src="/charmafa-logo.png" alt="CHARMAFA" class="w-10 h-10 object-contain" />
            </div>
            <div>
              <h1 class="text-2xl font-bold">Payment History</h1>
              <p class="text-sm text-green-100">Member Account Details</p>
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

    <main class="container mx-auto px-4 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
          <p class="text-gray-600 mt-4">Loading payment history...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        {{ error }}
      </div>

      <!-- Payment History Content -->
      <div v-if="!loading && !error">
        <!-- Member Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h2 class="text-2xl font-bold text-gray-800">{{ memberInfo.name }}</h2>
              <p class="text-gray-600">Account No: {{ memberInfo.account_no }}</p>
            </div>
            <div class="text-right">
              <span 
                :class="memberInfo.connection_status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ memberInfo.connection_status === 1 ? 'Connected' : 'Disconnected' }}
              </span>
            </div>
          </div>

          <!-- Summary Cards -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
              <p class="text-sm text-gray-600">Current Balance</p>
              <p class="text-xl font-bold text-blue-600">₱{{ formatNumber(summary.outstanding_balance) }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
              <p class="text-sm text-gray-600">Total Paid</p>
              <p class="text-xl font-bold text-green-600">₱{{ formatNumber(summary.total_paid) }}</p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
              <p class="text-sm text-gray-600">Last Payment</p>
              <p class="text-xl font-bold text-purple-600">₱{{ formatNumber(summary.last_payment_amount) }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(summary.last_payment_date) }}</p>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
              <p class="text-sm text-gray-600">Total Transactions</p>
              <p class="text-xl font-bold text-orange-600">{{ summary.total_transactions }}</p>
            </div>
          </div>
        </div>        <!-- Payment History Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h3 class="text-xl font-bold text-gray-800">Payment History</h3>
              <button 
                @click="printHistory" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print
              </button>
            </div>
          </div>

          <div id="printable-payment-history" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Date</th>
                  <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Reading</th>
                  <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Cu.M.</th>
                  <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Bill</th>
                  <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Paid</th>
                  <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Balance</th>
                  <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Method</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr 
                  v-for="payment in payments.data" 
                  :key="payment.payment_id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-gray-900 border-r border-gray-200">
                    {{ formatDate(payment.payment_date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 border-r border-gray-200">
                    {{ payment.meter_reading || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 border-r border-gray-200">
                    {{ payment.cum_usage || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right font-semibold border-r border-gray-200">
                    ₱{{ formatNumber(payment.bill_amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-green-600 border-r border-gray-200">
                    ₱{{ formatNumber(payment.amount_paid) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right font-semibold border-r border-gray-200">
                    <span :class="payment.balance > 0 ? 'text-red-600' : 'text-gray-900'">
                      ₱{{ formatNumber(payment.balance) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                    {{ payment.payment_method }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="payments.last_page > 1" class="p-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
              <div class="text-sm text-gray-600">
                Showing {{ payments.from || 0 }} to {{ payments.to || 0 }} of {{ payments.total || 0 }} transactions
              </div>
              <div class="flex gap-2">
                <button
                  @click="changePage(payments.current_page - 1)"
                  :disabled="payments.current_page <= 1"
                  class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded">
                  {{ payments.current_page }} of {{ payments.last_page }}
                </span>
                <button
                  @click="changePage(payments.current_page + 1)"
                  :disabled="payments.current_page >= payments.last_page"
                  class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
            </div>
          </div>

          <!-- No Data State -->
          <div v-if="payments.data && payments.data.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 text-lg">No payment history found</p>
            <p class="text-gray-400 text-sm">This member hasn't made any payments yet.</p>
          </div>
        </div>      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

import { ref, onMounted, nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useRouter, useRoute } from '#app'
import { useApi } from '~/composables/useApi'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const api = useApi()

// Reactive data
const loading = ref(false)
const error = ref('')
const memberInfo = ref({})
const payments = ref({ data: [] })
const summary = ref({})
const allPayments = ref([]) // For printing all records

// Get member ID from route params
const memberId = route.params.id

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const loadPaymentHistory = async (page = 1) => {
  loading.value = true
  error.value = ''
  
  try {
    const data = await api(`/members/${memberId}/payment-history?page=${page}`)
    memberInfo.value = data.member
    payments.value = data.payments
    summary.value = data.summary
  } catch (err) {
    error.value = `Failed to load payment history: ${err.message}`
    console.error('Payment history error:', err)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= payments.value.last_page) {
    loadPaymentHistory(page)
  }
}

const printHistory = async () => {
  try {
    // Load all payments for printing (without pagination)
    const data = await api(`/members/${memberId}/payment-history?all=true`)
    allPayments.value = data.payments.data || data.payments
    
    // Wait for data to be ready
    await nextTick()
    
    const printContent = document.getElementById('printable-payment-history')
    const printWindow = window.open('', '', 'height=600,width=800')
    
    printWindow.document.write('<html><head><title>Payment History</title>')
    printWindow.document.write('<style>')
    printWindow.document.write(`
      body { font-family: Arial, sans-serif; margin: 20px; }
      h1 { text-align: center; margin-bottom: 10px; }
      .print-header { text-align: center; margin-bottom: 20px; }
      .member-info { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; }
      table { width: 100%; border-collapse: collapse; font-size: 11px; }
      th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
      th { background-color: #d4edda; font-weight: bold; }
      tr:nth-child(even) { background-color: #f9f9f9; }
      .text-right { text-align: right; }
      .text-center { text-align: center; }
      .font-semibold { font-weight: 600; }
      .text-green-600 { color: #059669; }
      .text-red-600 { color: #dc2626; }
    `)
    printWindow.document.write('</style></head><body>')
    printWindow.document.write('<div class="print-header">')
    printWindow.document.write('<h1>CHARMAFA - Payment History</h1>')
    printWindow.document.write('<p>A Waterworks Service Association</p>')
    printWindow.document.write('<p>Generated: ' + new Date().toLocaleDateString() + '</p>')
    printWindow.document.write('</div>')
    
    // Add member info
    printWindow.document.write('<div class="member-info">')
    printWindow.document.write('<strong>Member:</strong> ' + memberInfo.value.name + '<br>')
    printWindow.document.write('<strong>Account No:</strong> ' + memberInfo.value.account_no + '<br>')
    printWindow.document.write('<strong>Status:</strong> ' + (memberInfo.value.connection_status === 1 ? 'Connected' : 'Disconnected'))
    printWindow.document.write('</div>')
    
    // Create table with all payments
    printWindow.document.write('<table>')
    printWindow.document.write('<thead>')
    printWindow.document.write('<tr>')
    printWindow.document.write('<th>Date</th>')
    printWindow.document.write('<th class="text-right">Reading</th>')
    printWindow.document.write('<th class="text-right">Cu.M.</th>')
    printWindow.document.write('<th class="text-right">Bill</th>')
    printWindow.document.write('<th class="text-right">Paid</th>')
    printWindow.document.write('<th class="text-right">Balance</th>')
    printWindow.document.write('<th>Method</th>')
    printWindow.document.write('</tr>')
    printWindow.document.write('</thead>')
    printWindow.document.write('<tbody>')
    
    allPayments.value.forEach(payment => {
      printWindow.document.write('<tr>')
      printWindow.document.write('<td>' + formatDate(payment.payment_date) + '</td>')
      printWindow.document.write('<td class="text-right">' + (payment.meter_reading || '-') + '</td>')
      printWindow.document.write('<td class="text-right">' + (payment.cum_usage || '-') + '</td>')
      printWindow.document.write('<td class="text-right font-semibold">₱' + formatNumber(payment.bill_amount) + '</td>')
      printWindow.document.write('<td class="text-right font-semibold text-green-600">₱' + formatNumber(payment.amount_paid) + '</td>')
      const balanceClass = payment.balance > 0 ? 'text-red-600' : ''
      printWindow.document.write('<td class="text-right font-semibold ' + balanceClass + '">₱' + formatNumber(payment.balance) + '</td>')
      printWindow.document.write('<td>' + payment.payment_method + '</td>')
      printWindow.document.write('</tr>')
    })
    
    printWindow.document.write('</tbody>')
    printWindow.document.write('</table>')
    printWindow.document.write('</body></html>')
    
    printWindow.document.close()
    printWindow.print()
  } catch (err) {
    console.error('Error loading full payment history for print:', err)
    // Fallback to current page data
    alert('Error loading payment history for printing. Please try again.')
  }
}

const goBack = () => {
  router.back()
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

onMounted(() => {
  authStore.initAuth()
  
  if (!authStore.isAuthenticated) {
    navigateTo('/')
    return
  }
  
  loadPaymentHistory()
})
</script>

<style scoped>
/* No additional styles needed for the popup printing approach */
</style>
