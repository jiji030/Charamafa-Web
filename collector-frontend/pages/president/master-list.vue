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
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Master List</h2>
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
            </div>            <select
              v-model="sortBy"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            >
              <option value="account_no">Sort By</option>
              <option value="account_no">Account No</option>
              <option value="name">Name</option>
              <option value="total">Total</option>
              <option value="date">Date</option>
            </select>            <!-- Billing Period / Month Filter Dropdown -->
            <select
              v-model="selectedPeriodId"
              @change="loadMembers"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
            >
              <option :value="null">Current (Live Data)</option>
              <option
                v-for="period in billingPeriods"
                :key="period.id"
                :value="period.id"
              >
                {{ period.label }}
              </option>
            </select>

            <!-- Save Month Button -->
            <button
              @click="generateSnapshotForThisMonth"
              :disabled="savingSnapshot"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print disabled:opacity-50"
            >
              <svg v-if="!savingSnapshot" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span v-if="!savingSnapshot">Save Month</span>
              <span v-else>Saving...</span>
            </button>

            <button
              @click="printMasterList"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Master List</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>            </button>
          </div>        </div>

        <!-- Snapshot message notification -->
        <div v-if="snapshotMessage" :class="[
          'p-4 border-b',
          snapshotMessage.includes('✓') 
            ? 'bg-green-50 text-green-800 border-green-200'
            : 'bg-red-50 text-red-800 border-red-200'
        ]">
          {{ snapshotMessage }}
        </div>

        <div id="printable-master-list" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-green-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Account No.</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Meter No.</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800 border-r border-gray-300">Account Name</th>                
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-800 border-r border-gray-300">C.U.M Consumption</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Minimum Amount / Month</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Excess C.U.M Per Month</th>
                <!-- <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Loss/Damage & other charges</th> -->
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Additional Charges</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Miscellaneous</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">ASELCO</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">DIESEL</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Other</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-800 border-r border-gray-300">Total</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-800">Date</th>
              </tr>
            </thead>            <tbody class="bg-white">
              <tr v-for="(member, index) in filteredMembers" :key="member.member_id" :class="[
                'hover:bg-gray-50 border-b border-gray-200',
                member.connection_status === 0 ? 'bg-red-100' : ''
              ]">
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ member.account_no }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200">{{ member.meter_no }}</td><td class="px-4 py-3 text-gray-900 border-r border-gray-200">
                  {{ member.name || (member.lname + ', ' + member.fname + (member.mname ? ' ' + member.mname.charAt(0) + '.' : '')) }}
                </td><td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-center">{{ member.cum_consumption || 0 }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.minimum_amount || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.excess_cum || 0).toFixed(2) }}</td>                
                <!-- <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.loss_damage || 0).toFixed(2) }}</td> -->                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">
                  <input
                    v-model.number="member.damage_charges"
                    @blur="updateDamageCharges(member)"
                    @keypress="validateNumericInput"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full text-left border-0 bg-transparent hover:bg-gray-50 focus:bg-white focus:border focus:border-green-500 focus:ring-1 focus:ring-green-500 rounded px-2 py-1 text-sm no-spinner"
                    :placeholder="(member.damage_charges || 0).toFixed(2)"
                  />
                </td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.miscellaneous || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.aselco || member.electricity_consumption || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.diesel || member.generator_consumption || 0).toFixed(2) }}</td>                <td class="px-4 py-3 text-gray-900 border-r border-gray-200 text-right">₱{{ (member.others || 0).toFixed(2) }}</td>
                <td class="px-4 py-3 font-semibold text-gray-900 border-r border-gray-200 text-right">₱{{ calculateTotalWithDamageCharges(member).toFixed(2) }}</td>
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
  { label: 'Dashboard', path: '/president/dashboard' },
  { label: 'Members', path: '/president/members' },
  { label: 'Expenses', path: '/president/expenses' },
  { label: 'Master-list', path: '/president/master-list' },
  { label: 'Collection', path: '/president/collection' },
  { label: 'Payment Status', path: '/president/payment-status' },
  { label: 'Important Info', path: '/president/important-info' },
  { label: 'Employee', path: '/president/employee' }
]

const members = ref([])
const loading = ref(false)
const searchQuery = ref('')
const sortBy = ref('account_no')
const sortOrder = ref('asc')
const authInitialized = ref(false)

// Billing period state (for record keeping / history)
const billingPeriods = ref([])
const selectedPeriodId = ref(null)
const selectedPeriodLabel = ref('Current (Live Data)')
const savingSnapshot = ref(false)
const snapshotMessage = ref('')

const loadBillingPeriods = async () => {
  try {
    const periods = await api('/billing-periods')
    billingPeriods.value = periods

    if (periods.length > 0) {
      // Determine current billing period based on billing cycle (10th to 9th)
      const now = new Date()
      let currentYear = now.getFullYear()
      let currentMonth = now.getMonth() + 1 // JS months 0-11, so add 1 for 1-12

      // Billing cycle: 10th of month X to 9th of month X+1
      // If today is between 1st-9th, we're in the previous month's billing cycle
      const day = now.getDate()
      if (day < 10) {
        currentMonth = currentMonth - 1
        if (currentMonth < 1) {
          currentMonth = 12
          currentYear = currentYear - 1
        }
      }

      const currentPeriod = periods.find(
        p => p.year === currentYear && p.month === currentMonth
      )

      if (currentPeriod) {
        selectedPeriodId.value = currentPeriod.id
        selectedPeriodLabel.value = currentPeriod.label
      } else {
        // fallback to most recent period
        selectedPeriodId.value = periods[0].id
        selectedPeriodLabel.value = periods[0].label
      }
    } else {
      selectedPeriodId.value = null
      selectedPeriodLabel.value = 'Current (Live Data)'
    }
  } catch (err) {
    console.error('Failed to load billing periods:', err)
    selectedPeriodId.value = null
    selectedPeriodLabel.value = 'Current (Live Data)'
  }
}

const generateSnapshotForThisMonth = async () => {
  const now = new Date()
  let year = now.getFullYear()
  let month = now.getMonth() + 1 // Current month (1-12)
  
  // Billing cycle: 10th of month X to 9th of month X+1
  // If today is between 1st-9th, we're in the previous month's billing cycle
  const day = now.getDate()
  if (day < 10) {
    month = month - 1
    if (month < 1) {
      month = 12
      year = year - 1
    }
  }

  savingSnapshot.value = true
  snapshotMessage.value = ''
  try {
    const result = await api('/billing-periods/generate', {
      method: 'POST',
      body: { year, month }
    })

    snapshotMessage.value = `✓ Monthly snapshot saved for ${result.label}`
    
    // Reload periods and select the newly generated one
    await loadBillingPeriods()
    
    setTimeout(() => {
      snapshotMessage.value = ''
    }, 3000)
  } catch (err) {
    snapshotMessage.value = `✗ Failed to save snapshot: ${err.message}`
    console.error('Failed to generate snapshot:', err)
  } finally {
    savingSnapshot.value = false
  }
}

onBeforeMount(() => {
  if (process.client) {
    authStore.initAuth()
    authInitialized.value = true
  }
})

const calculateTotal = (member) => {
  return calculateTotalWithDamageCharges(member)
}

const filteredMembers = computed(() => {
  let filtered = members.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(m => 
      m.account_no.toLowerCase().includes(query) ||
      (m.fname && m.fname.toLowerCase().includes(query)) ||
      (m.lname && m.lname.toLowerCase().includes(query)) ||
      (m.name && m.name.toLowerCase().includes(query)) ||
      (m.meter_no && m.meter_no.toLowerCase().includes(query))
    )
  }

  filtered = [...filtered].sort((a, b) => {
    let aVal, bVal
    
    if (sortBy.value === 'account_no') {
      aVal = a.account_no
      bVal = b.account_no
    } else if (sortBy.value === 'name') {
      // Use 'name' field if available (historical data), otherwise construct from fname/lname
      aVal = a.name || `${a.lname || ''}, ${a.fname || ''}`
      bVal = b.name || `${b.lname || ''}, ${b.fname || ''}`
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
    if (!selectedPeriodId.value) {
      // Live data - current master list
      const data = await api('/master-list')
      members.value = data
    } else {
      // Load historical snapshot for selected billing period
      const data = await api(`/monthly-master-list/${selectedPeriodId.value}`)
      members.value = data
    }
  } catch (err) {
    console.error('Failed to load members:', err)
  } finally {
    loading.value = false
  }
}

const printMasterList = () => {
  const printContent = document.getElementById('printable-master-list')
  const printWindow = window.open('', '', 'height=600,width=800')
  
  let periodInfo = ''
  if (selectedPeriodId.value) {
    periodInfo = `<p>Period: <strong>${selectedPeriodLabel.value}</strong></p>`
  } else {
    periodInfo = '<p>Period: <strong>Current (Live Data)</strong></p>'
  }

  printWindow.document.write('<html><head><title>Master List</title>')
  printWindow.document.write('<style>')
  printWindow.document.write(`
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { text-align: center; margin-bottom: 20px; }
    .period-info { text-align: center; font-weight: bold; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #d4edda; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    tr.disconnected { background-color: #fee2e2 !important; }
    tr.disconnected td { color: #991b1b; font-weight: bold; }
    .print-header { text-align: center; margin-bottom: 20px; }
  `)
  printWindow.document.write('</style></head><body>')
  printWindow.document.write('<div class="print-header">')
  printWindow.document.write('<h1>CHARMAFA - Master List</h1>')
  printWindow.document.write('<p>A Waterworks Service Association</p>')
  printWindow.document.write(periodInfo)
  printWindow.document.write('<p>Generated: ' + new Date().toLocaleDateString() + '</p>')
  printWindow.document.write('</div>')
  
  // Get the table and add class to disconnected rows
  const printableTable = printContent.cloneNode(true)
  const rows = printableTable.querySelectorAll('tbody tr')
  
  rows.forEach((row, index) => {
    const member = filteredMembers.value[index]
    if (member && member.connection_status === 0) {
      row.classList.add('disconnected')
    }
  })
  
  printWindow.document.write(printableTable.innerHTML)
  printWindow.document.write('</body></html>')
  
  printWindow.document.close()
  printWindow.print()
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

// Update damage charges function
const updateDamageCharges = async (member) => {
  try {
    if (selectedPeriodId.value) {
      // Updating snapshot data - update monthly_master_lists table
      await api(`/monthly-master-list/${member.id}`, {
        method: 'PUT',
        body: {
          damage_charges: parseFloat(member.damage_charges || 0)
        }
      })
    } else {
      // Updating live data - update members table
      await api(`/members/${member.member_id}/damage-charges`, {
        method: 'PUT',
        body: {
          damage_charges: parseFloat(member.damage_charges || 0)
        }
      })
    }
    
    // Recalculate total to include damage charges
    member.total = calculateTotalWithDamageCharges(member)
    
  } catch (err) {
    console.error('Failed to update damage charges:', err)
    // Optionally show error message to user
  }
}

// Calculate total including damage charges
const calculateTotalWithDamageCharges = (member) => {
  return (member.minimum_amount || 0) + 
         (member.excess_cum || 0) + 
         (member.loss_damage || 0) + 
         (member.miscellaneous || 0) + 
         (member.aselco || member.electricity_consumption || 0) + 
         (member.diesel || member.generator_consumption || 0) + 
         (member.others || 0) + 
         (parseFloat(member.damage_charges) || 0)
}

// Input validation to allow only numbers
const validateNumericInput = (event) => {
  const char = String.fromCharCode(event.which)
  if (!/[0-9\.]/.test(char)) {
    event.preventDefault()
  }
}

onMounted(() => {
  loadBillingPeriods().then(() => {
    loadMembers()
  })
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

/* Hide number input spinner arrows */
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
.no-spinner[type=number] {
  appearance: none;
  -moz-appearance: textfield;
}
</style>