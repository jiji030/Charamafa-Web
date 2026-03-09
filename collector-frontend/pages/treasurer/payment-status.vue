<template>
  <div class="min-h-screen bg-gray-100">
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
          <button
            @click="handleLogout"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white shadow-sm border-b">
      <div class="container mx-auto px-4">
        <div class="flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.path"
            @click="router.push(tab.path)"
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

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
      <!-- Input Form -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Payments Status (Bank Transactions)</h2>
        
        <form @submit.prevent="saveTransaction" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
              <select
                v-model="form.transaction_type"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              >
                <option value="">Select Type</option>
                <option value="Deposit">Deposit</option>
                <option value="Withdrawal">Withdrawal</option>
                <option value="Transfer">Transfer</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
              <input
                v-model.number="form.amount"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="₱0.00"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
              <input
                v-model="form.payment_date"
                type="date"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
              <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Add any notes about this transaction..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Attachment (Receipt)
            </label>
            <div class="flex items-center gap-4">
              <input
                type="file"
                @change="handleFileUpload"
                accept="image/*,.pdf"
                class="hidden"
                ref="fileInput"
              />
              <button
                type="button"
                @click="$refs.fileInput.click()"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
              >
                Choose file
              </button>
              <span class="text-sm text-gray-600">{{ fileName || 'No file chosen' }}</span>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ error }}
          </div>

          <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ success }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors disabled:bg-gray-400"
          >
            {{ loading ? 'Saving...' : 'Save Status' }}
          </button>
        </form>
      </div>

      <!-- Recent Transactions -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Recent Transactions</h2>          <div class="flex gap-4 items-center">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search"
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 w-64"
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <div class="flex gap-4 items-center border border-gray-300 rounded-lg px-4 py-2">
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="radio"
                  v-model="sortOrder"
                  value="asc"
                  class="text-green-600 focus:ring-green-500"
                />
                <span>Ascending</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="radio"
                  v-model="sortOrder"
                  value="desc"
                  class="text-green-600 focus:ring-green-500"
                />
                <span>Descending</span>
              </label>
            </div>

            <select
              v-model="sortBy"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            >
              <option value="date">Sort By</option>
              <option value="date">Date</option>
              <option value="transaction_type">Transaction Type</option>
              <option value="amount">Amount</option>
            </select>

            <button
              @click="printTransactions"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Transaction</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
          </div>
        </div>

        <div id="printable-transactions" class="overflow-x-auto">
          <table class="w-full text-sm">            <thead class="bg-green-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Transaction Type</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800">Notes</th>
              </tr>
            </thead>
            <tbody class="bg-white">              <tr v-for="(transaction, index) in filteredTransactions" :key="transaction.transaction_id" class="hover:bg-gray-50 border-b border-gray-200">
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ transaction.transaction_type }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (transaction.amount || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ formatDate(transaction.payment_date) }}</td>
                <td class="px-4 py-3 text-gray-900">{{ transaction.notes }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredTransactions.length === 0" class="text-center py-12">
          <p class="text-gray-600">No transactions found</p>
        </div>
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
  { label: 'Master List', path: '/treasurer/master-list' },
  { label: 'Expenses', path: '/treasurer/expenses' },
  { label: 'Collection', path: '/treasurer/collection' },
  { label: 'Payment Status', path: '/treasurer/payment-status' }
]

const form = ref({
  transaction_type: '',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0]
})

const transactions = ref([])
const searchQuery = ref('')
const sortBy = ref('date')
const sortOrder = ref('desc')
const loading = ref(false)
const error = ref('')
const success = ref('')
const fileName = ref('')
const fileInput = ref(null)

const filteredTransactions = computed(() => {
  let filtered = transactions.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(t => 
      t.transaction_type.toLowerCase().includes(query) ||
      (t.notes && t.notes.toLowerCase().includes(query))
    )
  }

  // Sort
  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'date') {
      aVal = new Date(a.payment_date)
      bVal = new Date(b.payment_date)
    } else if (sortBy.value === 'transaction_type') {
      aVal = a.transaction_type
      bVal = b.transaction_type
    } else if (sortBy.value === 'amount') {
      aVal = a.amount || 0
      bVal = b.amount || 0
    }

    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return filtered
})

const loadTransactions = async () => {
  try {
    const data = await api('/payment-transactions')
    transactions.value = data
  } catch (err) {
    console.error('Failed to load transactions:', err)
    // Mock data for demo
    transactions.value = [
      {
        transaction_id: 1,
        transaction_type: 'Deposit',
        amount: 5000,
        payment_date: '2023-12-31',
        status: 'completed',
        attachment_path: null
      }
    ]
  }
}

const saveTransaction = async () => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await api('/payment-transactions', {
      method: 'POST',
      body: form.value
    })

    success.value = 'Transaction saved successfully!'
    
    // Reset form
    form.value = {
      transaction_type: '',
      amount: 0,
      payment_date: new Date().toISOString().split('T')[0],
      notes: ''
    }
    fileName.value = ''

    // Reload transactions
    await loadTransactions()

    // Clear success message after 3 seconds
    setTimeout(() => {
      success.value = ''
    }, 3000)
  } catch (err) {
    error.value = 'Failed to save transaction. Please try again.'
  } finally {
    loading.value = false
  }
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    fileName.value = file.name
    // TODO: Implement file upload to server
  }
}

const printTransactions = () => {
  const printContent = document.getElementById('printable-transactions')
  const printWindow = window.open('', '', 'height=600,width=800')
  
  printWindow.document.write('<html><head><title>Payment Status</title>')
  printWindow.document.write('<style>')
  printWindow.document.write(`
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #d4edda; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .print-header { text-align: center; margin-bottom: 20px; }
  `)
  printWindow.document.write('</style></head><body>')
  printWindow.document.write('<div class="print-header">')
  printWindow.document.write('<h1>CHARMAFA - Payment Status</h1>')
  printWindow.document.write('<p>A Waterworks Service Association</p>')
  printWindow.document.write('<p>Generated: ' + new Date().toLocaleDateString() + '</p>')
  printWindow.document.write('</div>')
  printWindow.document.write(printContent.innerHTML)
  printWindow.document.write('</body></html>')
  
  printWindow.document.close()
  printWindow.print()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}

onMounted(() => {
  loadTransactions()
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>