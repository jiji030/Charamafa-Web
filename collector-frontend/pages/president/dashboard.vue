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
      <!-- Welcome Section -->
      <div class="mb-5">
      </div>      <!-- Key Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Members Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">ACTIVE MEMBERS</p>
              <p class="text-2xl font-bold text-green-600">{{ activeMembers }}</p>
              <p class="text-xs text-gray-500">Connected members</p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
              <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Unpaid Consumers Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500 cursor-pointer hover:bg-red-50 transition-colors" @click="showUnpaidMembers">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">UNPAID CONSUMERS</p>
              <p class="text-2xl font-bold text-red-600">{{ unpaidMembers }}</p>
              <p class="text-xs text-gray-500">Click to view details</p>
            </div>
            <div class="p-3 bg-red-100 rounded-full">
              <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Monthly Collection Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">MONTHLY COLLECTION</p>
              <p class="text-2xl font-bold text-blue-600">₱{{ formatNumber(monthlyCollection) }}</p>
              <p class="text-xs text-gray-500">{{ currentCollectionPeriod }}</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
              <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Total Members Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">TOTAL MEMBERS</p>
              <p class="text-2xl font-bold text-purple-600">{{ totalMembers }}</p>
              <p class="text-xs text-gray-500">All registered members</p>
            </div>            <div class="p-3 bg-purple-100 rounded-full">
              <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
              </svg>
            </div>
          </div>
        </div>      </div>      <!-- Payment Analytics and Side Panels Section -->
      <div class="grid grid-cols-1 xl:grid-cols-10 gap-8 mb-8">
        <!-- Payment Analytics (Left Side - 70%) -->
        <div class="xl:col-span-7 bg-white rounded-lg shadow-md p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Payment Analytics</h3>
            <select v-model="selectedPeriod" @change="loadAnalytics" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option value="current">Current Month</option>
              <option value="last">Last Month</option>
              <option value="quarter">Last 3 Months</option>
              <option value="year">This Year</option>
            </select>
          </div>        
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- <div class="text-center p-4 bg-green-50 rounded-lg">
              <p class="text-2xl font-bold text-green-600">{{ paidMembers }}</p>
              <p class="text-sm text-gray-600">Paid Members</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors" @click="showUnpaidMembers">
              <p class="text-2xl font-bold text-red-600">{{ unpaidMembers }}</p>
              <p class="text-sm text-gray-600">Unpaid Members</p>
            </div> -->
            <div class="text-center p-4 bg-blue-50 rounded-lg">
              <p class="text-2xl font-bold text-blue-600">₱{{ formatNumber(totalCollected) }}</p>
              <p class="text-sm text-gray-600">Total Collected</p>
            </div>
          </div>
          
          <!-- Payment Trends Chart -->
          <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
              <h4 class="text-lg font-medium text-gray-800">Payment Trends</h4>
              <select v-model="trendsMonths" @change="loadTrends" class="px-3 py-1 border border-gray-300 rounded text-sm">
                <option value="6">Last 6 months</option>
                <option value="12">Last 12 months</option>
              </select>
            </div>
            
            <div v-if="trendsLoading" class="text-center py-8">
              <p class="text-gray-500">Loading trends...</p>
            </div>          
            <div v-else-if="trendsData.length === 0" class="text-center py-8">
              <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
              </svg>
              <p class="text-gray-500">No payment data available</p>
              <button @click="loadTrends" class="mt-2 px-3 py-1 bg-blue-500 text-white text-xs rounded">Retry</button>
            </div>          
            <div v-else>
              <div class="relative h-64">
                <canvas ref="trendsChart" class="w-full h-full"></canvas>
                <div v-if="chartError" class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded">
                  <div class="text-center">
                    <p class="text-red-500 text-sm">Chart loading failed</p>
                    <button @click="retryChart" class="mt-2 px-3 py-1 bg-red-500 text-white text-xs rounded">Retry</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Side Panels (30%) -->
        <div class="xl:col-span-3 space-y-6">
          <!-- Connection Status Panel -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Connection Status</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span class="text-sm text-gray-600">Connected</span>
                </div>
                <span class="text-sm font-medium">{{ activeMembers }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                  <span class="text-sm text-gray-600">Disconnected</span>
                </div>
                <span class="text-sm font-medium">{{ disconnectedMembers }}</span>
              </div>
            </div>
          </div>

          <!-- Recent Collections Panel -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Collections</h3>
            <div class="space-y-3">
              <div v-for="collection in recentCollections" :key="collection.id" class="flex justify-between items-center py-2 border-b border-gray-100">
                <div>
                  <p class="font-medium text-gray-800">{{ collection.month }}</p>
                </div>
                <p class="font-bold text-green-600">₱{{ formatNumber(collection.total_collection) }}</p>
              </div>
            </div>          
            <button @click="navigateTo('/president/collection')" class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition-colors">
              View All Collections
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Unpaid Members Modal -->
    <div v-if="showUnpaidModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeUnpaidModal">
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[80vh] overflow-hidden" @click.stop>
        <div class="p-6 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Unpaid Members ({{ unpaidMembersList.length }})</h3>
            <button @click="closeUnpaidModal" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[60vh]">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Payment</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="member in unpaidMembersList" :key="member.member_id" class="hover:bg-gray-50">
                  <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ member.account_no }}</td>
                  <td class="px-4 py-4 text-sm text-gray-900">{{ member.fname }} {{ member.lname }}</td>
                  <td class="px-4 py-4 text-sm font-bold text-red-600">₱{{ formatNumber(member.prev_balance || 0) }}</td>
                  <td class="px-4 py-4 text-sm">
                    <span :class="member.connection_status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                          class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ member.connection_status === 1 ? 'Connected' : 'Disconnected' }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-600">
                    {{ member.last_payment_date ? formatDate(member.last_payment_date) : 'Never' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="unpaidMembersList.length === 0" class="text-center py-8">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-500">All members have paid their current bills!</p>
          </div>
        </div>
        
        <!-- <div class="p-6 border-t border-gray-200 bg-gray-50">
          <button @click="closeUnpaidModal" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
            Close
          </button>
        </div> -->
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

// Dashboard Data
const totalMembers = ref(0)
const activeMembers = ref(0)
const unpaidMembers = ref(0)
const monthlyCollection = ref(0)
const currentCollectionPeriod = ref('')
const selectedPeriod = ref('current')

// Analytics Data
const paidMembers = ref(0)
const disconnectedMembers = ref(0)
const totalCollected = ref(0)

// Collections Data
const recentCollections = ref([])
const unpaidMembersList = ref([])
const showUnpaidModal = ref(false)

// Trends Data
const trendsData = ref([])
const trendsLoading = ref(false)
const trendsMonths = ref(6)
const trendsChart = ref(null)
const chartError = ref(false)
let chartInstance = null

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const loadDashboardData = async () => {
  try {
    // Load analytics data
    const analyticsData = await api('/dashboard/analytics')
    totalMembers.value = analyticsData.total_members || 0
    activeMembers.value = analyticsData.active_members || 0
    paidMembers.value = analyticsData.paid_members || 0
    unpaidMembers.value = analyticsData.unpaid_members || 0
    totalCollected.value = analyticsData.total_collected || 0
    disconnectedMembers.value = analyticsData.disconnected_members || 0
    monthlyCollection.value = analyticsData.monthly_collection || 0
    
    // Set current collection period (resets every 10th)
    const now = new Date()
    const currentDay = now.getDate()
    let periodStart, periodEnd
    
    if (currentDay >= 10) {
      // Current period: 10th of current month to 9th of next month
      periodStart = new Date(now.getFullYear(), now.getMonth(), 10)
      periodEnd = new Date(now.getFullYear(), now.getMonth() + 1, 9)
    } else {
      // Current period: 10th of last month to 9th of current month
      periodStart = new Date(now.getFullYear(), now.getMonth() - 1, 10)
      periodEnd = new Date(now.getFullYear(), now.getMonth(), 9)
    }
    
    currentCollectionPeriod.value = `${formatDate(periodStart)} - ${formatDate(periodEnd)}`
    
    // Load recent collections
    const collectionsData = await api('/collections?limit=5')
    recentCollections.value = collectionsData || []
    
  } catch (err) {
    console.error('Failed to load dashboard data:', err)
  }
}

const loadAnalytics = async () => {
  try {
    console.log('Loading analytics for period:', selectedPeriod.value)
    const analyticsData = await api(`/dashboard/analytics?period=${selectedPeriod.value}`)
    // console.log('Analytics API response:', analyticsData)
    paidMembers.value = analyticsData.paid_members || 0
    unpaidMembers.value = analyticsData.unpaid_members || 0
    totalCollected.value = analyticsData.total_collected || 0
    
  } catch (err) {
    console.error('Failed to load analytics data:', err)
  }
}

const loadTrends = async () => {
  trendsLoading.value = true
  try {
    console.log('Loading trends for months:', trendsMonths.value)
    
    const response = await api(`/dashboard/monthly-trends?months=${trendsMonths.value}`)
    // console.log('Trends API response:', response)
    trendsData.value = response || []
    
    // Ensure DOM is updated before creating chart
    await nextTick()
    
    // Add a small delay to ensure canvas is properly rendered
    setTimeout(() => {
      if (trendsChart.value && trendsData.value.length > 0) {
        console.log('Creating trends chart...')
        createTrendsChart()
      } else {
        console.log('Cannot create chart:', {
          hasCanvas: !!trendsChart.value,
          dataLength: trendsData.value.length
        })
      }
    }, 100)
    
  } catch (err) {
    console.error('Failed to load trends data:', err)
    console.error('Error details:', err.response || err)
    trendsData.value = []
  } finally {
    trendsLoading.value = false
  }
}

const createTrendsChart = async () => {
  if (!trendsChart.value || !trendsData.value.length) {
    console.log('Cannot create chart:', { 
      hasElement: !!trendsChart.value, 
      hasData: trendsData.value.length 
    })
    return
  }
  
  try {
    console.log('Creating chart with data:', trendsData.value)
    chartError.value = false
    
    // Dynamically import Chart.js
    const { Chart, registerables } = await import('chart.js')
    Chart.register(...registerables)
    
    // Destroy existing chart
    if (chartInstance) {
      chartInstance.destroy()
      chartInstance = null
    }
    
    const ctx = trendsChart.value.getContext('2d')
      // Prepare chart data
    const labels = trendsData.value.map(item => {
      const date = new Date(item.month + '-01')
      return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
    })
    
    const data = trendsData.value.map(item => parseFloat(item.total_amount))
    
    console.log('Chart labels:', labels)
    console.log('Chart data:', data)
    console.log('Full trends data:', trendsData.value)
    
    // Create new chart
    chartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Monthly Collections',
          data: data,
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#10B981',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index'
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: '#f3f4f6'
            },
            ticks: {
              callback: function(value) {
                return '₱' + value.toLocaleString()
              },
              color: '#6b7280'
            }
          },
          x: {
            grid: {
              color: '#f3f4f6'
            },
            ticks: {
              color: '#6b7280'
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: '#10B981',
            borderWidth: 1,
            callbacks: {
              title: function(context) {
                const dataIndex = context[0].dataIndex
                const item = trendsData.value[dataIndex]
                if (item.period_start && item.period_end) {
                  const startDate = new Date(item.period_start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                  const endDate = new Date(item.period_end).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                  return `Collection Period: ${startDate} - ${endDate}`
                }
                return context[0].label
              },
              label: function(context) {
                const dataIndex = context.dataIndex
                const item = trendsData.value[dataIndex]
                return [
                  'Total: ₱' + context.parsed.y.toLocaleString(),
                  'Payments: ' + (item.payment_count || 0) + ' transactions'
                ]
              }
            }
          }
        }
      }
    })
    
    // console.log('Chart created successfully:', chartInstance)
    
  } catch (error) {
    console.error('Error creating chart:', error)
    chartError.value = true
  }
}

const retryChart = () => {
  chartError.value = false
  createTrendsChart()
}

const showUnpaidMembers = async () => {
  try {
    const unpaidData = await api('/dashboard/unpaid-members')
    unpaidMembersList.value = unpaidData || []
    showUnpaidModal.value = true
  } catch (err) {
    console.error('Failed to load unpaid members:', err)
  }
}

const closeUnpaidModal = () => {
  showUnpaidModal.value = false
  unpaidMembersList.value = []
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

onMounted(async () => {
  console.log('Dashboard component mounted')
  
  // Ensure auth is initialized
  if (process.client) {
    authStore.initAuth()
    await nextTick()
    
    if (!authStore.isAuthenticated) {
      await navigateTo('/')
      return
    }
  }
  
  // Load dashboard data
  loadDashboardData()
  loadTrends()
})
</script>

<style scoped>
/* Additional styling if needed */
</style>
