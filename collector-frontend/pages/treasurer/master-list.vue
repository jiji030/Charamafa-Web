<template>
  <!-- Show loading while auth is being initialized -->
  <div v-if="!authStore.user && !authInitialized" class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-700 mx-auto mb-4"></div>
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
          <h2 class="text-xl font-bold text-gray-800">Master List</h2>
          <div class="flex gap-4 items-center">
            <div class="relative">              <input
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
              <option value="account_no">Sort By</option>
              <option value="account_no">Account No</option>
              <option value="name">Name</option>
              <option value="total">Total</option>
              <option value="date">Date</option>
            </select>

            <button
              @click="printMasterList"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Master List</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
          </div>
        </div>

        <div id="printable-master-list" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-green-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Account No.</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Meter No.</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Account Name</th>                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-800 border-r border-gray-300">C.U.M Consumption</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Minimum Amount / Month</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Excess C.U.M Per Month</th>                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Loss/Damage & other charges</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Miscellaneous</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">ASELCO</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">DIESEL</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Other</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Total</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800">Date</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr v-for="(member, index) in filteredMembers" :key="member.member_id" class="hover:bg-gray-50 border-b border-gray-200">
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ member.account_no }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ member.meter_no }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">
                  {{ member.lname }}, {{ member.fname }} {{ member.mname ? member.mname.charAt(0) + '.' : '' }}
                </td>                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-center">{{ member.cum_consumption || 0 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.minimum_amount || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.excess_cum || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.loss_damage || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.vat_amount || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.aselco || 0).toFixed(2) }}</td>                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.diesel || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.other || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 font-semibold text-gray-900 border-r border-gray-200 text-right">₱{{ (member.total || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 whitespace-nowrap">{{ member.billing_date || '09-12-2025' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="loading" class="text-center py-12">
          <p class="text-gray-600">Loading members...</p>
        </div>

        <div v-if="!loading && filteredMembers.length === 0" class="text-center py-12">
          <p class="text-gray-600">No members found</p>
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

const members = ref([])
const loading = ref(false)
const searchQuery = ref('')
const sortBy = ref('account_no')
const sortOrder = ref('asc')
const authInitialized = ref(false)

onBeforeMount(() => {
  // Initialize auth before mounting
  if (process.client) {
    authStore.initAuth()
    authInitialized.value = true
  }
})

const calculateTotal = (member) => {
  return member.total || 0
}

const filteredMembers = computed(() => {
  let filtered = members.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(m => 
      m.account_no.toLowerCase().includes(query) ||
      m.fname.toLowerCase().includes(query) ||
      m.lname.toLowerCase().includes(query) ||
      m.meter_no.toLowerCase().includes(query)
    )
  }

  // Sort
  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'account_no') {
      aVal = a.account_no
      bVal = b.account_no
    } else if (sortBy.value === 'name') {
      aVal = `${a.lname}, ${a.fname}`
      bVal = `${b.lname}, ${b.fname}`
    } else if (sortBy.value === 'total') {
      aVal = calculateTotal(a)
      bVal = calculateTotal(b)
    } else if (sortBy.value === 'date') {
      aVal = a.billing_date || '09-12-2025'
      bVal = b.billing_date || '09-12-2025'
    }

    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return filtered
})

const loadMembers = async () => {
  loading.value = true
  try {
    const data = await api('/master-list')
    members.value = data
    // console.log('Loaded members:', data)
  } catch (err) {
    console.error('Failed to load members:', err)
  } finally {
    loading.value = false
  }
}

const printMasterList = () => {
  const printContent = document.getElementById('printable-master-list')
  const printWindow = window.open('', '', 'height=600,width=800')
  
  printWindow.document.write('<html><head><title>Master List</title>')
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
  printWindow.document.write('<h1>CHARMAFA - Master List</h1>')
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
  loadMembers()
})
</script>

<style scoped>
@media print {
  header, nav, .no-print {
    display: none !important;
  }
  
  table {
    page-break-inside: auto;
  }
  
  tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }
}
</style>