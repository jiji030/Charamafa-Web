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

    <main class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Water Consumption Report</h2>
          <div class="flex gap-4 items-center">
            <select
              v-model="selectedPeriodId"
              @change="loadReportData"
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

            <button
              @click="printReport"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 no-print"
            >
              <span>Print Report</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
          </div>
        </div>

        <div id="report-content" class="p-6">
          <div v-if="loading" class="text-center py-12">
            <p class="text-gray-600">Loading report data...</p>
          </div>

          <div v-else-if="Object.keys(membersByTS).length === 0" class="text-center py-12">
            <p class="text-gray-600">No data available for the selected period</p>
          </div>

          <div v-else>
            <div class="mb-8">
              <h1 class="text-2xl font-bold text-center mb-2">CHARMAFA - Water Consumption Report</h1>
              <p class="text-center text-gray-600 mb-4">A Waterworks Service Association</p>
              <p class="text-center text-sm text-gray-500">
                Period: <strong>{{ selectedPeriodLabel }}</strong> | Generated: {{ new Date().toLocaleDateString() }}
              </p>
            </div>

            <div v-for="tsGroup in Object.values(membersByTS)" :key="tsGroup.ts_id" class="mb-12 page-break">
              <div class="border-l-4 border-green-600 pl-4 mb-6 py-2">
                <p class="text-2xl font-bold text-gray-800">TS {{ tsGroup.ts_no }}</p>
                <p class="text-gray-600 mt-1">{{ tsGroup.landmark || 'No Landmark' }}</p>
              </div>

              <table class="w-full text-base border-collapse mb-8">
                <thead>
                  <tr class="bg-green-100 border-b-2 border-gray-800">
                    <th class="border px-3 py-2 text-left font-semibold text-sm">Meter No</th>
                    <th class="border px-3 py-2 text-left font-semibold text-sm">Account No</th>
                    <th class="border px-3 py-2 text-left font-semibold text-sm">Account Name</th>
                    <th colspan="2" class="border px-3 py-2 text-center font-semibold text-sm">Consumption (CUM)</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">Minimum</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">Excess</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">Damage</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">Misc</th>
                    <th colspan="2" class="border px-3 py-2 text-center font-semibold text-sm">Meter Reading</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">ASELCO</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">DIESEL</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">Other</th>
                    <th class="border px-3 py-2 text-right font-semibold text-sm">Total</th>
                  </tr>
                  <tr class="bg-green-50 border-b border-gray-300">
                    <th colspan="3" class="border px-3 py-1"></th>
                    <th class="border px-2 py-1 text-center font-semibold text-xs">Previous</th>
                    <th class="border px-2 py-1 text-center font-semibold text-xs">Present</th>
                    <th colspan="4" class="border px-3 py-1"></th>
                    <th class="border px-2 py-1 text-center font-semibold text-xs">Previous</th>
                    <th class="border px-2 py-1 text-center font-semibold text-xs">Present</th>
                    <th colspan="3" class="border px-3 py-1"></th>
                    <th class="border px-3 py-1"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="member in tsGroup.members" :key="member.member_id" class="border-b hover:bg-gray-50 text-base"
                    :class="member.connection_status === 0 ? 'bg-red-200' : ''">
                    <td class="border px-3 py-2 text-center text-sm">{{ member.meter_no || '-' }}</td>
                    <td class="border px-3 py-2 text-center text-sm">{{ member.account_no || '-' }}</td>
                    <td class="border px-3 py-2 text-sm">{{ getMemberName(member) }}</td>
                    <td class="border px-2 py-2 text-center text-sm">{{ member.prev_CUM_consumption || 0 }}</td>
                    <td class="border px-2 py-2 text-center text-sm">{{ member.present_CUM_consumption || 0 }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.minimum_amount || 0).toFixed(2) }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.excess_cum || 0).toFixed(2) }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.damage_charges || 0).toFixed(2) }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.miscellaneous || 0).toFixed(2) }}</td>
                    <td class="border px-2 py-2 text-center text-sm">{{ member.prev_meter_reading || '-' }}</td>
                    <td class="border px-2 py-2 text-center text-sm">{{ member.present_meter_reading || '-' }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.aselco || 0).toFixed(2) }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.diesel || 0).toFixed(2) }}</td>
                    <td class="border px-3 py-2 text-right text-sm">₱{{ (member.others || 0).toFixed(2) }}</td>
                    <td class="border px-3 py-2 text-right text-sm font-semibold bg-yellow-50">₱{{ calculateTotal(member).toFixed(2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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
  { label: 'Payment Status', path: '/treasurer/payment-status' },
  { label: 'Consumption Report', path: '/treasurer/consumption-report' }
]

const loading = ref(false)
const billingPeriods = ref([])
const selectedPeriodId = ref(null)
const selectedPeriodLabel = ref('Current (Live Data)')
const membersByTS = ref({})

const loadBillingPeriods = async () => {
  try {
    const periods = await api('/billing-periods')
    billingPeriods.value = periods

    if (periods.length > 0) {
      const now = new Date()
      let currentYear = now.getFullYear()
      let currentMonth = now.getMonth() + 1

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
      }
    }
  } catch (err) {
    console.error('Failed to load billing periods:', err)
  }
}

const loadReportData = async () => {
  loading.value = true
  membersByTS.value = {}

  try {
    let data = []
    
    if (!selectedPeriodId.value) {
      data = await api('/master-list')
      selectedPeriodLabel.value = 'Current (Live Data)'
    } else {
      data = await api(`/monthly-master-list/${selectedPeriodId.value}`)
      const period = billingPeriods.value.find(p => p.id === selectedPeriodId.value)
      if (period) {
        selectedPeriodLabel.value = period.label
      }
    }

    console.log('Raw data from API:', data)

    // Build TS groups - normalize ts_id to ensure consistency
    const tsGroups = {}
    
    data.forEach(member => {
      // Get ts_id from either field (live data uses ts_Id, historical uses ts_id)
      const tsId = member.ts_Id !== undefined ? member.ts_Id : (member.ts_id || 'Unknown')
      const tsNo = member.ts_no || ''
      const landmark = member.landmark || ''
      
      console.log(`Member ${member.account_no}: tsId=${tsId}, ts_no=${tsNo}, landmark=${landmark}`)
      
      // Create group if it doesn't exist
      if (!tsGroups[tsId]) {
        tsGroups[tsId] = {
          ts_id: tsId,
          ts_no: tsNo,
          landmark: landmark,
          members: []
        }
      }
      
      tsGroups[tsId].members.push(member)
    })
    
    // Sort groups by ts_id
    const sortedTsIds = Object.keys(tsGroups).sort((a, b) => {
      const aNum = parseInt(a)
      const bNum = parseInt(b)
      if (isNaN(aNum) || isNaN(bNum)) return String(a).localeCompare(String(b))
      return aNum - bNum
    })
    
    // Rebuild membersByTS in sorted order
    sortedTsIds.forEach(tsId => {
      membersByTS.value[tsId] = tsGroups[tsId]
    })
    
    console.log('Grouped data:', membersByTS.value)
  } catch (err) {
    console.error('Failed to load report data:', err)
  } finally {
    loading.value = false
  }
}

const getMemberName = (member) => {
  return member.name || `${member.lname || ''}, ${member.fname || ''} ${member.mname ? member.mname.charAt(0) + '.' : ''}`.trim()
}

const calculateTotal = (member) => {
  return (member.minimum_amount || 0) +
         (member.excess_cum || 0) +
         (member.damage_charges || 0) +
         (member.miscellaneous || 0) +
         (member.aselco || 0) +
         (member.diesel || 0) +
         (member.others || 0)
}

const printReport = () => {
  const reportContent = document.getElementById('report-content')
  const printWindow = window.open('', '', 'width=1800,height=1000')

  const tsGroups = reportContent.querySelectorAll('.page-break')
  let tablesHtml = ''
  tsGroups.forEach(group => {
    tablesHtml += group.outerHTML
  })

  const htmlContent = `
    <html>
    <head>
      <title>Water Consumption Report</title>
      <meta charset="UTF-8">
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }
        body { 
          font-family: Arial, sans-serif; 
          font-size: 13px;
          padding: 8mm;
          width: 100%;
          overflow-x: hidden;
        }
        @page { size: landscape; margin: 8mm; }
        @media print {
          * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
          body { width: 100%; margin: 0; padding: 8mm; }
        }
        h1 { text-align: center; font-size: 24px; margin-bottom: 8px; font-weight: bold; }
        .report-meta { text-align: center; font-size: 12px; margin-bottom: 25px; }
        .report-meta p { margin: 3px 0; }
        .page-break { page-break-inside: avoid; margin-bottom: 25px; width: 100%; }
        .bg-gray-900 {
          background-color: #1a1a1a;
          color: white;
          padding: 12px 15px;
          margin-bottom: 15px;
          border-radius: 4px;
          page-break-inside: avoid;
          border-left: 4px solid #22c55e;
        }
        .bg-gray-900 p:first-child { font-size: 18px; font-weight: bold; margin: 0; }
        .bg-gray-900 p { margin: 3px 0; font-size: 12px; }
        .border-t { border-top: 1px solid #444; }
        .bg-gray-900 .border-t { margin-top: 8px; padding-top: 8px; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
          font-size: 11px;
          table-layout: fixed;
        }
        th {
          background-color: #90EE90;
          border: 1px solid #333;
          padding: 5px 2px;
          text-align: left;
          font-weight: bold;
          font-size: 10px;
          word-wrap: break-word;
        }
        td {
          border: 1px solid #999;
          padding: 4px 2px;
          text-align: left;
          word-wrap: break-word;
          font-size: 10px;
        }
        td.text-center { text-align: center; }
        td.text-right { text-align: right; }
        tr.bg-red-200 { background-color: #fecaca !important; }
        tr.bg-red-200 td { color: #7f1d1d; font-weight: bold; }
        tr.bg-yellow-50 { background-color: #fefce8; }
        .footer { text-align: center; margin-top: 25px; font-size: 10px; page-break-inside: avoid; }
      </style>
    </head>
    <body>
      <h1>CHARMAFA - Water Consumption Report</h1>
      <div class="report-meta">
        <p>A Waterworks Service Association</p>
        <p>Period: <strong>${selectedPeriodLabel.value}</strong> | Generated: ${new Date().toLocaleDateString()}</p>
      </div>
      ${tablesHtml}
      <div class="footer">
        <p>This report was generated on ${new Date().toLocaleString()}</p>
      </div>
    </body>
    </html>
  `

  printWindow.document.write(htmlContent)
  printWindow.document.close()

  setTimeout(() => {
    printWindow.print()
  }, 500)
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}

onMounted(() => {
  loadBillingPeriods().then(() => {
    loadReportData()
  })
})
</script>

<style scoped>
@media print {
  header, nav, .no-print {
    display: none !important;
  }

  html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: visible !important;
  }

  #report-content {
    width: 100%;
    margin: 0;
    padding: 8mm;
    overflow: visible;
  }

  .page-break {
    page-break-inside: avoid;
    page-break-after: auto;
    width: 100%;
  }

  table {
    page-break-inside: avoid;
    width: 100%;
  }

  tr {
    page-break-inside: avoid;
  }
}
</style>
