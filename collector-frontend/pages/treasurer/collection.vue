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
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Payment Collection (Monthly)</h2>          <div class="flex gap-4 items-center">
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
              <option value="amount">Amount</option>
              <option value="collector">Collector</option>
            </select>

            <button
              @click="printCollection"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Collection</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
          </div>
        </div>

        <div id="printable-collection" class="overflow-x-auto">
          <table class="w-full">            <thead class="bg-green-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Month</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Total Collection</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Collector</th>
              </tr>
            </thead>            <tbody class="divide-y divide-gray-200">
              <tr v-for="collection in filteredCollections" :key="collection.collection_id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-900">{{ collection.month }}</td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">₱{{ collection.total_collection.toLocaleString() }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ collection.collector_name }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-green-50">
              <tr>
                <td class="px-6 py-4 text-sm font-bold text-gray-900">TOTAL</td>
                <td class="px-6 py-4 text-sm font-bold text-green-700 text-right">₱{{ totalCollections.toLocaleString() }}</td>
                <td class="px-6 py-4"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div v-if="filteredCollections.length === 0" class="text-center py-12">
          <p class="text-gray-600">No collections found</p>
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

const collections = ref([])
const searchQuery = ref('')
const sortBy = ref('date')
const sortOrder = ref('desc')

const filteredCollections = computed(() => {
  let filtered = collections.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(c => 
      c.month.toLowerCase().includes(query) ||
      c.collector_name.toLowerCase().includes(query)
    )
  }

  // Sort
  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'date') {
      aVal = new Date(a.month)
      bVal = new Date(b.month)
    } else if (sortBy.value === 'amount') {
      aVal = a.total_collection || 0
      bVal = b.total_collection || 0
    } else if (sortBy.value === 'collector') {
      aVal = a.collector_name
      bVal = b.collector_name
    }

    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return filtered
})

const totalCollections = computed(() => {
  return collections.value.reduce((sum, c) => sum + c.total_collection, 0)
})

const loadCollections = async () => {
  try {
    const data = await api('/collections')
    collections.value = data
  } catch (err) {
    console.error('Failed to load collections:', err)
    // Mock data for demo
    collections.value = [
    ]
  }
}

const printCollection = () => {
  const printContent = document.getElementById('printable-collection')
  const printWindow = window.open('', '', 'height=600,width=800')
  
  printWindow.document.write('<html><head><title>Collection Report</title>')
  printWindow.document.write('<style>')
  printWindow.document.write(`
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #d4edda; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    tfoot { background-color: #d4edda; font-weight: bold; }
    .print-header { text-align: center; margin-bottom: 20px; }
  `)
  printWindow.document.write('</style></head><body>')
  printWindow.document.write('<div class="print-header">')
  printWindow.document.write('<h1>CHARMAFA - Collection Report</h1>')
  printWindow.document.write('<p>A Waterworks Service Association</p>')
  printWindow.document.write('<p>Generated: ' + new Date().toLocaleDateString() + '</p>')
  printWindow.document.write('</div>')
  printWindow.document.write(printContent.innerHTML)
  printWindow.document.write('</body></html>')
  
  printWindow.document.close()
  printWindow.print()
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}

onMounted(() => {
  loadCollections()
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>