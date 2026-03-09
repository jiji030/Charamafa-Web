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

    <!-- Main Content -->
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

      <!-- Input Expenses Form -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Input Expenses</h2>
        
        <form @submit.prevent="saveExpense" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
              <input
                v-model="form.date"
                type="date"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Expense Type</label>
              <select
                v-model="form.expense_type"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              >
                <option value="">Select Type</option>
                <option value="Regular">Regular</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Emergency">Emergency</option>
                <option value="Supplies">Supplies</option>
                <option value="Salary">Salary</option>
                <option value="Others">Others</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Unsa (What was bought/paid for)
            </label>
            <input
              v-model="form.what_was_bought_paid_for"
              type="text"
              required
              placeholder="e.g. Pipe Repair, Office Supplies, Pump Maintenance"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Asa gigamit (Where it was used)
            </label>
            <input
              v-model="form.where_it_was_used"
              type="text"
              required
              placeholder="e.g. Purok Station, Office"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
            <input
              v-model.number="form.amount"
              type="number"
              step="0.01"
              min="0"
              required
              placeholder="0.00"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            />
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
            {{ loading ? 'Saving...' : 'Save Expenses' }}
          </button>
        </form>
      </div>

      <!-- Expense List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Expense List</h2>          <div class="flex gap-4 items-center">
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
              <option value="expense_type">Expense Type</option>
              <option value="amount">Amount</option>
              <option value="what_was_bought_paid_for">Item/Service</option>
            </select>

            <button
              @click="printExpenses"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Expenses</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
          </div>
        </div>

        <div id="printable-expenses" class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-green-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Unsa</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Asa gigamit</th>                <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Amount</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Receipt</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase no-print">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="expense in filteredExpenses" :key="expense.expense_id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(expense.date) }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ expense.expense_type }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ expense.what_was_bought_paid_for }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ expense.where_it_was_used }}</td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">₱{{ expense.amount.toFixed(2) }}</td>
                <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ expense.receipt_path ? '✓' : '—' }}</td>
                <td class="px-6 py-4 text-sm no-print text-center">
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal(expense)"
                      class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteExpense(expense.expense_id)"
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

        <div v-if="filteredExpenses.length === 0" class="text-center py-12">
          <p class="text-gray-600">No expenses found</p>
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
            <h3 class="text-2xl font-bold text-gray-800">Edit Expense</h3>
            <button @click="closeEditModal" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <form @submit.prevent="updateExpense" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input
                  v-model="editForm.date"
                  type="date"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Expense Type</label>
                <select
                  v-model="editForm.expense_type"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                >
                  <option value="">Select Type</option>
                  <option value="Regular">Regular</option>
                  <option value="Maintenance">Maintenance</option>
                  <option value="Emergency">Emergency</option>
                  <option value="Supplies">Supplies</option>
                  <option value="Salary">Salary</option>
                  <option value="Others">Others</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Unsa (What was bought/paid for)
              </label>
              <input
                v-model="editForm.what_was_bought_paid_for"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Asa gigamit (Where it was used)
              </label>
              <input
                v-model="editForm.where_it_was_used"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
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

            <div v-if="editError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
              {{ editError }}
            </div>

            <div class="flex gap-4 pt-4 border-t">
              <button
                type="submit"
                :disabled="saving"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400"
              >
                {{ saving ? 'Updating...' : 'Update Expense' }}
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
  date: new Date().toISOString().split('T')[0],
  expense_type: '',
  what_was_bought_paid_for: '',
  where_it_was_used: '',
  amount: 0
})

const editForm = ref({
  expense_id: null,
  date: '',
  expense_type: '',
  what_was_bought_paid_for: '',
  where_it_was_used: '',
  amount: 0
})

const expenses = ref([])
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

const filteredExpenses = computed(() => {
  let filtered = expenses.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(e => 
      e.expense_type.toLowerCase().includes(query) ||
      e.what_was_bought_paid_for.toLowerCase().includes(query) ||
      e.where_it_was_used.toLowerCase().includes(query)
    )
  }

  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'date') {
      aVal = new Date(a.date)
      bVal = new Date(b.date)
    } else if (sortBy.value === 'amount') {
      aVal = a.amount
      bVal = b.amount
    } else if (sortBy.value === 'expense_type') {
      aVal = a.expense_type.toLowerCase()
      bVal = b.expense_type.toLowerCase()
    } else if (sortBy.value === 'what_was_bought_paid_for') {
      aVal = a.what_was_bought_paid_for.toLowerCase()
      bVal = b.what_was_bought_paid_for.toLowerCase()
    }
    
    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return filtered
})

const loadExpenses = async () => {
  try {
    expenses.value = await api('/expenses')
  } catch (err) {
    console.error('Failed to load expenses:', err)
  }
}

const saveExpense = async () => {
  loading.value = true
  error.value = ''

  try {
    await api('/expenses', {
      method: 'POST',
      body: form.value
    })

    notificationMessage.value = 'Expense saved successfully!'
    showNotification.value = true
    
    // Reset form
    form.value = {
      date: new Date().toISOString().split('T')[0],
      expense_type: '',
      what_was_bought_paid_for: '',
      where_it_was_used: '',
      amount: 0
    }
    fileName.value = ''

    // Reload expenses
    await loadExpenses()

    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    error.value = 'Failed to save expense. Please try again.'
  } finally {
    loading.value = false
  }
}

const openEditModal = (expense) => {
  editForm.value = {
    expense_id: expense.expense_id,
    date: expense.date.split('T')[0],
    expense_type: expense.expense_type,
    what_was_bought_paid_for: expense.what_was_bought_paid_for,
    where_it_was_used: expense.where_it_was_used,
    amount: expense.amount
  }
  showEditModal.value = true
  editError.value = ''
}

const closeEditModal = () => {
  showEditModal.value = false
  editError.value = ''
}

const updateExpense = async () => {
  saving.value = true
  editError.value = ''

  try {
    await api(`/expenses/${editForm.value.expense_id}`, {
      method: 'PUT',
      body: editForm.value
    })

    notificationMessage.value = 'Expense updated successfully!'
    showNotification.value = true
    
    closeEditModal()
    await loadExpenses()

    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    editError.value = 'Failed to update expense. Please try again.'
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

const deleteExpense = async (id) => {
  if (!confirm('Are you sure you want to delete this expense?')) return

  try {
    await api(`/expenses/${id}`, { method: 'DELETE' })
    
    notificationMessage.value = 'Expense deleted successfully!'
    showNotification.value = true
    
    await loadExpenses()

    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    alert('Failed to delete expense')
  }
}

const printExpenses = () => {
  const printWindow = window.open('', '', 'height=600,width=800')
  
  printWindow.document.write('<html><head><title>Expenses List</title>')
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
  printWindow.document.write('<h1>CHARMAFA - Expenses List</h1>')
  printWindow.document.write('<p>A Waterworks Service Association</p>')
  printWindow.document.write('<p>Generated: ' + new Date().toLocaleDateString() + '</p>')
  printWindow.document.write('</div>')
  
  // Create table without Action column
  printWindow.document.write('<table>')
  printWindow.document.write('<thead>')
  printWindow.document.write('<tr>')
  printWindow.document.write('<th>Date</th>')
  printWindow.document.write('<th>Type</th>')
  printWindow.document.write('<th>Unsa</th>')
  printWindow.document.write('<th>Asa gigamit</th>')
  printWindow.document.write('<th class="text-right">Amount</th>')
  printWindow.document.write('<th class="text-center">Receipt</th>')
  printWindow.document.write('</tr>')
  printWindow.document.write('</thead>')
  printWindow.document.write('<tbody>')
  
  // Add expense rows without Action column
  filteredExpenses.value.forEach((expense, index) => {
    const rowClass = index % 2 === 1 ? ' style="background-color: #f9f9f9;"' : ''
    printWindow.document.write(`<tr${rowClass}>`)
    printWindow.document.write(`<td>${formatDate(expense.date)}</td>`)
    printWindow.document.write(`<td>${expense.expense_type}</td>`)
    printWindow.document.write(`<td>${expense.what_was_bought_paid_for}</td>`)
    printWindow.document.write(`<td>${expense.where_it_was_used}</td>`)
    printWindow.document.write(`<td class="text-right">₱${expense.amount.toFixed(2)}</td>`)
    printWindow.document.write(`<td class="text-center">${expense.receipt_path ? '✓' : '—'}</td>`)
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
  loadExpenses()
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