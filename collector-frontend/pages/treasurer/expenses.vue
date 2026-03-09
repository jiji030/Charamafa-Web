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
              placeholder="e.g. Pipe Repair, Office Supplies, Pump Maintenance, Office"
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

          <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ success }}
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
              <option value="date">Sort By</option>
              <option value="date">Date</option>
              <option value="expense_type">Expense Type</option>
              <option value="amount">Amount</option>
              <option value="what_bought">What was bought</option>
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
          <table class="w-full text-sm">
            <thead class="bg-green-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Expense Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">What was bought/paid for</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Where it was used</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800">Amount</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr v-for="(expense, index) in filteredExpenses" :key="expense.expense_id" class="hover:bg-gray-50 border-b border-gray-200">
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ formatDate(expense.date) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ expense.expense_type }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ expense.what_was_bought_paid_for }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ expense.where_it_was_used }}</td>
                <td class="px-4 py-3 text-gray-900 text-right">₱{{ (expense.amount || 0).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredExpenses.length === 0" class="text-center py-12">
          <p class="text-gray-600">No expenses found</p>
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
  date: new Date().toISOString().split('T')[0],
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
const error = ref('')
const success = ref('')
const fileName = ref('')
const fileInput = ref(null)

const filteredExpenses = computed(() => {
  let filtered = expenses.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(e => 
      e.expense_type.toLowerCase().includes(query) ||
      e.what_was_bought_paid_for.toLowerCase().includes(query) ||
      e.where_it_was_used.toLowerCase().includes(query)
    )
  }

  // Sort
  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'date') {
      aVal = new Date(a.date)
      bVal = new Date(b.date)
    } else if (sortBy.value === 'expense_type') {
      aVal = a.expense_type
      bVal = b.expense_type
    } else if (sortBy.value === 'amount') {
      aVal = a.amount || 0
      bVal = b.amount || 0
    } else if (sortBy.value === 'what_bought') {
      aVal = a.what_was_bought_paid_for
      bVal = b.what_was_bought_paid_for
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
  success.value = ''

  try {
    await api('/expenses', {
      method: 'POST',
      body: form.value
    })

    success.value = 'Expense saved successfully!'
    
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

    // Clear success message after 3 seconds
    setTimeout(() => {
      success.value = ''
    }, 3000)
  } catch (err) {
    error.value = 'Failed to save expense. Please try again.'
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

const deleteExpense = async (id) => {
  if (!confirm('Are you sure you want to delete this expense?')) return

  try {
    await api(`/expenses/${id}`, { method: 'DELETE' })
    await loadExpenses()
  } catch (err) {
    alert('Failed to delete expense')
  }
}

const printExpenses = () => {
  const printContent = document.getElementById('printable-expenses')
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
  `)
  printWindow.document.write('</style></head><body>')
  printWindow.document.write('<div class="print-header">')
  printWindow.document.write('<h1>CHARMAFA - Expenses List</h1>')
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
  loadExpenses()
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>