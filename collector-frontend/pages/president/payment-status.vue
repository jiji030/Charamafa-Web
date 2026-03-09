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

      <!-- Input Form -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Payment Status (Bank Transactions)</h2>
        
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

          <button
            type="submit"
            :disabled="loading"
            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors disabled:bg-gray-400"
          >
            {{ loading ? 'Saving...' : 'Save Transaction' }}
          </button>
        </form>
      </div>

      <!-- Recent Transactions -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Recent Transactions</h2>
          <div class="flex gap-4 items-center">
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
              <option value="date">Sort By: Date</option>
              <option value="amount">Amount</option>
              <option value="transaction_type">Transaction Type</option>
              <option value="status">Status</option>
            </select>

            <button
              @click="printTransactions"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Transactions</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
          </div>
        </div>        <div id="printable-transactions" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-green-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Transaction Type</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Date</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-800 border-r border-gray-300">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Notes</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-800 no-print">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr v-for="(transaction, index) in filteredTransactions" :key="transaction.transaction_id" class="hover:bg-gray-50 border-b border-gray-200">
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ transaction.transaction_type }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (transaction.amount || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ formatDate(transaction.payment_date) }}</td>
                <td class="px-4 py-3 border-r border-gray-200 text-center">
                  <span
                    :class="{
                      'bg-green-100 text-green-800': transaction.status === 'completed',
                      'bg-yellow-100 text-yellow-800': transaction.status === 'pending',
                      'bg-red-100 text-red-800': transaction.status === 'cancelled'
                    }"
                    class="px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ transaction.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ transaction.notes || '—' }}</td>
                <td class="px-4 py-3 text-center no-print">
                  <div class="flex gap-2 justify-center">
                    <button
                      @click="openEditModal(transaction)"
                      class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteTransaction(transaction.transaction_id)"
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

        <div v-if="filteredTransactions.length === 0" class="text-center py-12">
          <p class="text-gray-600">No transactions found</p>
        </div>
      </div>
    </main>

    <!-- Edit Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeEditModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6 border-b sticky top-0 bg-white z-10">
          <div class="flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-800">Edit Transaction</h3>
            <button @click="closeEditModal" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <form @submit.prevent="updateTransaction" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
                <select
                  v-model="editForm.transaction_type"
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
                  v-model.number="editForm.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
              <input
                v-model="editForm.payment_date"
                type="date"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                v-model="editForm.status"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              >
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea
                v-model="editForm.notes"
                rows="2"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              ></textarea>
            </div>

            <div v-if="editError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
              {{ editError }}
            </div>

            <div class="flex gap-4 pt-4 border-t">
              <button
                type="submit"
                :disabled="saving"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400"
              >
                {{ saving ? 'Updating...' : 'Update Transaction' }}
              </button>
              <button
                type="button"
                @click="closeEditModal"
                :disabled="saving"
                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-200"
              >
                Cancel
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

const form = ref({
  transaction_type: '',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  notes: ''
})

const editForm = ref({
  transaction_id: null,
  transaction_type: '',
  amount: 0,
  payment_date: '',
  status: 'completed',
  notes: ''
})

const transactions = ref([])
const searchQuery = ref('')
const sortBy = ref('date')
const sortOrder = ref('desc')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editError = ref('')
const fileName = ref('')
const fileInput = ref(null)
const showEditModal = ref(false)
const showNotification = ref(false)
const notificationMessage = ref('')

const filteredTransactions = computed(() => {
  let filtered = transactions.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(t => 
      t.transaction_type.toLowerCase().includes(query) ||
      t.status.toLowerCase().includes(query)
    )
  }

  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'date') {
      aVal = new Date(a.payment_date)
      bVal = new Date(b.payment_date)
    } else if (sortBy.value === 'amount') {
      aVal = a.amount
      bVal = b.amount
    } else if (sortBy.value === 'transaction_type') {
      aVal = a.transaction_type.toLowerCase()
      bVal = b.transaction_type.toLowerCase()
    } else if (sortBy.value === 'status') {
      aVal = a.status.toLowerCase()
      bVal = b.status.toLowerCase()
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
    // Mock data for demo purposes
    transactions.value = [
      {
        transaction_id: 1,
        transaction_type: 'Deposit',
        amount: 5000,
        payment_date: '2023-12-31',
        status: 'completed',
        notes: 'Initial deposit'
      }
    ]
  }
}

const saveTransaction = async () => {
  loading.value = true
  error.value = ''

  try {
    await api('/payment-transactions', {
      method: 'POST',
      body: {
        ...form.value,
        status: 'completed'
      }
    })

    notificationMessage.value = 'Transaction saved successfully!'
    showNotification.value = true
    
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

    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    error.value = 'Failed to save transaction. Please try again.'
  } finally {
    loading.value = false
  }
}

const openEditModal = (transaction) => {
  editForm.value = {
    transaction_id: transaction.transaction_id,
    transaction_type: transaction.transaction_type,
    amount: transaction.amount,
    payment_date: transaction.payment_date.split('T')[0],
    status: transaction.status,
    notes: transaction.notes || ''
  }
  showEditModal.value = true
  editError.value = ''
}

const closeEditModal = () => {
  showEditModal.value = false
  editError.value = ''
}

const updateTransaction = async () => {
  saving.value = true
  editError.value = ''

  try {
    await api(`/payment-transactions/${editForm.value.transaction_id}`, {
      method: 'PUT',
      body: editForm.value
    })

    notificationMessage.value = 'Transaction updated successfully!'
    showNotification.value = true
    
    closeEditModal()
    await loadTransactions()

    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    editError.value = 'Failed to update transaction. Please try again.'
  } finally {
    saving.value = false
  }
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    fileName.value = file.name
    // TODO: Implement file upload to server
  }
}

const deleteTransaction = async (id) => {
  if (!confirm('Are you sure you want to delete this transaction?')) return

  try {
    await api(`/payment-transactions/${id}`, { method: 'DELETE' })
    
    notificationMessage.value = 'Transaction deleted successfully!'
    showNotification.value = true
    
    await loadTransactions()

    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    alert('Failed to delete transaction')
  }
}

const printTransactions = () => {
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
    .text-right { text-align: right; }
    .text-center { text-align: center; }
  `)
  printWindow.document.write('</style></head><body>')
  printWindow.document.write('<div class="print-header">')
  printWindow.document.write('<h1>CHARMAFA - Payment Status</h1>')
  printWindow.document.write('<p>A Waterworks Service Association</p>')
  printWindow.document.write('<p>Generated: ' + new Date().toLocaleDateString() + '</p>')
  printWindow.document.write('</div>')
  
  // Create table without Actions column
  printWindow.document.write('<table>')
  printWindow.document.write('<thead>')
  printWindow.document.write('<tr>')
  printWindow.document.write('<th>No</th>')
  printWindow.document.write('<th>Transaction Type</th>')
  printWindow.document.write('<th class="text-right">Amount</th>')
  printWindow.document.write('<th>Date</th>')
  printWindow.document.write('<th class="text-center">Status</th>')
  printWindow.document.write('<th>Notes</th>')
  printWindow.document.write('</tr>')
  printWindow.document.write('</thead>')
  printWindow.document.write('<tbody>')
  
  // Add transaction rows without Actions column
  filteredTransactions.value.forEach((transaction, index) => {
    const rowClass = index % 2 === 1 ? ' style="background-color: #f9f9f9;"' : ''
    printWindow.document.write(`<tr${rowClass}>`)
    printWindow.document.write(`<td>${index + 1}</td>`)
    printWindow.document.write(`<td>${transaction.transaction_type}</td>`)
    printWindow.document.write(`<td class="text-right">₱${(transaction.amount || 0).toFixed(2)}</td>`)
    printWindow.document.write(`<td>${formatDate(transaction.payment_date)}</td>`)
    printWindow.document.write(`<td class="text-center">${transaction.status}</td>`)
    printWindow.document.write(`<td>${transaction.notes || '—'}</td>`)
    printWindow.document.write('</tr>')
  })
  
  printWindow.document.write('</tbody>')
  printWindow.document.write('</table>')
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
  navigateTo('/')
}

onMounted(() => {
  loadTransactions()
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

@media print {
  .no-print,
  .no-print * {
    display: none !important;
    visibility: hidden !important;
  }
  
  /* Hide all elements with no-print class and their children */
  th.no-print,
  td.no-print,
  button.no-print,
  div.no-print {
    display: none !important;
  }
  
  /* Ensure print layout is clean */
  body {
    background: white !important;
  }
  
  /* Hide browser elements that shouldn't print */
  header,
  nav,
  .fixed,
  .sticky {
    display: none !important;
  }
}
</style>