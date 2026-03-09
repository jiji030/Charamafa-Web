<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black opacity-50"></div>

      <!-- Modal content -->
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <button 
          @click="$emit('close')" 
          class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <h3 class="text-lg font-bold text-gray-900 mb-4">
          Connect Bluetooth Printer
        </h3>

        <div v-if="loading" class="text-center py-4">
          <p class="text-gray-600">Scanning for printers...</p>
        </div>

        <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
          {{ error }}
          <button 
            @click="requestPermission" 
            class="text-red-700 underline ml-2"
          >
            Grant Permission
          </button>
        </div>

        <div v-else>
          <div v-if="devices.length === 0" class="text-center py-4">
            <p class="text-gray-600 mb-4">No printers found</p>
            <button 
              @click="scanDevices" 
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Scan Again
            </button>
          </div>

          <ul v-else class="space-y-2 mb-4">
            <li 
              v-for="device in devices" 
              :key="device.id"
              class="flex items-center justify-between p-3 border rounded hover:bg-gray-50 cursor-pointer"
              @click="connectDevice(device)"
            >
              <div>
                <p class="font-medium">{{ device.name || 'Unknown Device' }}</p>
                <p class="text-sm text-gray-500">{{ device.id }}</p>
              </div>
              <span v-if="selectedDevice?.id === device.id" class="text-green-600">
                Connected
              </span>
              <button 
                v-else
                class="text-blue-600 hover:text-blue-800"
              >
                Connect
              </button>
            </li>
          </ul>

          <div class="flex justify-end space-x-3">
            <button
              @click="scanDevices"
              class="px-4 py-2 text-blue-600 hover:text-blue-800"
            >
              Refresh
            </button>
            <button
              @click="$emit('close')"
              class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'device-selected'])

const devices = ref([])
const loading = ref(false)
const error = ref('')
const selectedDevice = ref(null)

const requestPermission = async () => {
  try {
    const permissionStatus = await navigator.permissions.query({ name: 'bluetooth' })
    if (permissionStatus.state === 'granted') {
      scanDevices()
    }
  } catch (err) {
    error.value = 'Bluetooth permission is required'
  }
}

const scanDevices = async () => {
  loading.value = true
  error.value = ''
  devices.value = []

  try {
    const device = await navigator.bluetooth.requestDevice({
      filters: [
        { services: ['000018f0-0000-1000-8000-00805f9b34fb'] }, // Generic printer service UUID
        { services: ['e7810a71-73ae-499d-8c15-faa9aef0c3f2'] }, // Common thermal printer service
      ],
      optionalServices: ['battery_service']
    })
    
    devices.value = [device]
  } catch (err) {
    console.error('Bluetooth scan error:', err)
    if (err.name === 'NotFoundError') {
      error.value = 'No compatible printers found'
    } else if (err.name === 'SecurityError') {
      error.value = 'Bluetooth permission denied'
    } else {
      error.value = 'Failed to scan for printers'
    }
  } finally {
    loading.value = false
  }
}

const connectDevice = async (device) => {
  loading.value = true
  error.value = ''

  try {
    const server = await device.gatt.connect()
    selectedDevice.value = device
    // Store the device ID in localStorage for persistence
    localStorage.setItem('selectedPrinterId', device.id)
    emit('device-selected', device)
  } catch (err) {
    console.error('Connection error:', err)
    error.value = 'Failed to connect to printer'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Check for stored printer ID
  const savedPrinterId = localStorage.getItem('selectedPrinterId')
  if (savedPrinterId && devices.value.length > 0) {
    const savedDevice = devices.value.find(d => d.id === savedPrinterId)
    if (savedDevice) {
      selectedDevice.value = savedDevice
    }
  }
})
</script>
