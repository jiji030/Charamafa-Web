<!-- components/BluetoothThermalPrinter.vue -->

<template>
  <div>
    <!-- Connect Button -->
    <button
      v-if="!connected"
      @click="connectPrinter"
      :disabled="!isBluetoothAvailable"
      class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center gap-2"
    >
      <span>🔵</span>
      <span>{{ isBluetoothAvailable ? 'Connect Bluetooth Printer' : 'Bluetooth Not Available' }}</span>
    </button>

    <!-- Status Display -->
    <div v-if="connected" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
      <p class="text-green-800 font-semibold">✓ Printer Connected: {{ printerName }}</p>
      <button
        @click="disconnect"
        class="mt-2 text-sm text-red-600 hover:text-red-800"
      >
        Disconnect
      </button>
    </div>

    <!-- Error Display -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <!-- Browser Warning -->
    <div v-if="!isBluetoothAvailable" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
      <p class="text-yellow-800 font-semibold mb-2">⚠️ Web Bluetooth Not Supported</p>
      <p class="text-sm text-yellow-700">
        Please use Chrome, Edge, or Opera browser. Firefox and Safari don't support Web Bluetooth.
      </p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  receiptData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['connected', 'disconnected', 'printed'])

const connected = ref(false)
const printerName = ref('')
const error = ref('')
const device = ref(null)
const characteristic = ref(null)
const isBluetoothAvailable = ref(false)

const getMemberName = (member) => {
  if (!member) return ''
  return `${member.fname || ''} ${member.mname || ''} ${member.lname || ''}`.trim()
}

const formatMoney = (amount) => {
  return Number(amount || 0).toFixed(2)
}

onMounted(() => {
  if (process.client) {
    isBluetoothAvailable.value = 'bluetooth' in navigator
  }
})

const connectPrinter = async () => {
  try {
    error.value = ''
    
    // Request Bluetooth device
    const bluetoothDevice = await navigator.bluetooth.requestDevice({
      // Accept all devices (thermal printers use various UUIDs)
      acceptAllDevices: true,
      optionalServices: [
        '000018f0-0000-1000-8000-00805f9b34fb', // Common thermal printer service
        '0000ff00-0000-1000-8000-00805f9b34fb', // Alternative service
        'e7810a71-73ae-499d-8c15-faa9aef0c3f2'  // Another common service
      ]
    })

    printerName.value = bluetoothDevice.name

    const server = await bluetoothDevice.gatt.connect()
    
    // Try to find the correct service
    let service
    let char
    
    try {
      // Try first common service
      service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb')
      char = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb')
    } catch (e1) {
      try {
        // Try alternative service
        service = await server.getPrimaryService('0000ff00-0000-1000-8000-00805f9b34fb')
        const characteristics = await service.getCharacteristics()
        char = characteristics[0] // Use first writable characteristic
      } catch (e2) {
        // Try third option
        service = await server.getPrimaryService('e7810a71-73ae-499d-8c15-faa9aef0c3f2')
        const characteristics = await service.getCharacteristics()
        char = characteristics[0]
      }
    }

    device.value = bluetoothDevice
    characteristic.value = char
    connected.value = true
    
    emit('connected', bluetoothDevice.name)
  } catch (err) {
    console.error('Connection error:', err)
    error.value = `Failed to connect: ${err.message}`
  }
}

const disconnect = () => {
  if (device.value?.gatt?.connected) {
    device.value.gatt.disconnect()
  }
  device.value = null
  characteristic.value = null
  connected.value = false
  printerName.value = ''
  emit('disconnected')
}

const printReceipt = async (receiptData) => {
  if (!connected.value || !characteristic.value) {
    error.value = 'Printer not connected'
    return false
  }

  try {
    error.value = ''
    
    // ESC/POS commands
    const ESC = '\x1B'
    const GS = '\x1D'
    const INIT = ESC + '@' // Initialize printer
    const ALIGN_CENTER = ESC + 'a' + '1'
    const ALIGN_LEFT = ESC + 'a' + '0'
    const BOLD_ON = ESC + 'E' + '1'
    const BOLD_OFF = ESC + 'E' + '0'
    const CUT = GS + 'V' + '1' // Partial cut
    const LINE = '--------------------------------\n'    // Build receipt
    let receipt = INIT
    
    // Header
    receipt += ALIGN_CENTER + BOLD_ON
    receipt += 'CHARMAFA\n'
    receipt += BOLD_OFF
    receipt += 'Water Association\n'
    receipt += new Date().toLocaleString('en-PH', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    }) + '\n'
    receipt += LINE
      // Determine receipt type and title
    const isReconnectionReceipt = receiptData.reconnection_fee !== undefined
    receipt += BOLD_ON
    if (isReconnectionReceipt) {
      receipt += 'RECONNECTION RECEIPT\n'
    } else {
      receipt += 'PAYMENT RECEIPT\n'
    }
    
    // Add copy type if specified
    if (receiptData.copyType) {
      receipt += `${receiptData.copyType}\n`
    }
    
    receipt += BOLD_OFF
    receipt += LINE
      // Member info
    receipt += ALIGN_LEFT
    receipt += `Account No: ${receiptData.member?.account_no || 'N/A'}\n`
    receipt += `Name: ${receiptData.member?.name || getMemberName(receiptData.member) || 'N/A'}\n`
    receipt += `Meter No: ${receiptData.member?.meter_no || 'N/A'}\n`
    receipt += `INVOICE Number: ${receiptData.or_number || 'N/A'}\n`
    receipt += LINE
      // Bill details based on receipt type
    if (isReconnectionReceipt) {
      // Reconnection receipt format
      receipt += `Reconnection Fee: P${formatMoney(receiptData.reconnection_fee)}\n`
      if (receiptData.prev_balance_paid > 0) {
        receipt += `Previous Balance: P${formatMoney(receiptData.prev_balance_paid)}\n`
      }
      receipt += BOLD_ON
      receipt += LINE
      receipt += `Total Amount:     P${formatMoney(receiptData.total_amount)}\n\n`
      receipt += BOLD_OFF
      receipt += `Cash Received:    P${formatMoney(receiptData.cash)}\n`
      receipt += BOLD_ON
      receipt += `Change:           P${formatMoney(receiptData.change)}\n`
      if (receiptData.remaining_balance > 0) {
        receipt += `Remaining Bal:    P${formatMoney(receiptData.remaining_balance)}\n`
      }
      receipt += BOLD_OFF
    } else {
      // Regular payment receipt format
      receipt += `Prev Balance:   P${formatMoney(receiptData.prev_balance)}\n`
      receipt += LINE
      receipt += `Total Bill:     P${formatMoney(receiptData.total_bill)}\n`
      receipt += `Cash Received:  P${formatMoney(receiptData.cash)}\n`
      receipt += BOLD_ON
      receipt += `Change:         P${formatMoney(receiptData.change)}\n`
      receipt += `Remaining Bal:  P${formatMoney(receiptData.remaining_balance)}\n`
      receipt += BOLD_OFF
    }
    receipt += LINE
      // Footer
    receipt += ALIGN_CENTER
    receipt += 'Thank you for your payment!\n'
    
    // Add copy-specific note if provided
    if (receiptData.copyNote) {
      receipt += `${receiptData.copyNote}\n`
    } else {
      receipt += 'Keep this receipt for your records\n'
    }
    
    receipt += LINE
    receipt += `Processed by: ${receiptData.processed_by || 'N/A'}\n`
    receipt += 'Official Receipt\n'
    receipt += '\n\n\n'
    receipt += CUT

    // Convert to bytes and send
    const encoder = new TextEncoder()
    const data = encoder.encode(receipt)
    
    // Send in chunks (some printers have buffer limits)
    const chunkSize = 512
    for (let i = 0; i < data.length; i += chunkSize) {
      const chunk = data.slice(i, i + chunkSize)
      await characteristic.value.writeValue(chunk)
      await new Promise(resolve => setTimeout(resolve, 100)) // Small delay between chunks
    }
    
    emit('printed')
    return true
  } catch (err) {
    console.error('Print error:', err)
    error.value = `Print failed: ${err.message}`
    return false
  }
}

// Expose methods to parent
defineExpose({
  printReceipt,
  connectPrinter,
  disconnect,
  isConnected: computed(() => connected.value)
})
</script>