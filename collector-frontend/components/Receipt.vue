<template>
  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-2xl w-full mx-4" ref="receiptRef">
      <!-- Print-only content -->
      <div class="print-content">
        <!-- Receipt Header -->
        <div class="text-center mb-6">
          <img src="/charmafa-logo.png" alt="CHARMAFA Logo" class="mx-auto h-16 mb-2">
          <h1 class="text-xl font-bold">CHARMAFA WATER BILLING</h1>
          <p class="text-sm text-gray-600">Charito, Bayugan City</p>
          <div class="border-b-2 border-black my-4"></div>
        </div>

        <!-- Receipt Details -->
        <div class="mb-6 flex justify-between text-sm">
          <div>
            <p><span class="font-semibold">OR No:</span> {{ receiptData?.or_number }}</p>
            <p><span class="font-semibold">Date:</span> {{ formatDate(receiptData?.payment_date) }}</p>
          </div>
          <div>
            <p><span class="font-semibold">Bill Month:</span> {{ formatDate(receiptData?.member?.bill_month) }}</p>
          </div>
        </div>

        <!-- Member Details -->
        <div class="mb-6">
          <h2 class="font-bold mb-2 text-lg">Member Information</h2>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <p><span class="font-semibold">Name:</span> {{ receiptData?.member?.name }}</p>
            <p><span class="font-semibold">Member ID:</span> {{ receiptData?.member?.member_id }}</p>
            <p><span class="font-semibold">Purok:</span> {{ receiptData?.member?.purok }}</p>
            <p><span class="font-semibold">TS Number:</span> {{ receiptData?.member?.ts_number }}</p>
          </div>
        </div>

        <!-- Payment Details -->
        <div class="mb-6">
          <h2 class="font-bold mb-2 text-lg">Payment Details</h2>
          <div class="border-t border-b border-gray-200 py-4">
            <div class="flex justify-between mb-2">
              <span>Previous Balance:</span>
              <span class="font-semibold">₱{{ formatAmount(receiptData?.member?.prev_balance) }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span>Current Bill:</span>
              <span class="font-semibold">₱{{ formatAmount(receiptData?.member?.bill_amount) }}</span>
            </div>
            <div class="flex justify-between font-bold text-lg mt-4 pt-2 border-t">
              <span>Amount Paid:</span>
              <span>₱{{ formatAmount(receiptData?.amount) }}</span>
            </div>
            <div v-if="receiptData?.remaining_balance > 0" class="flex justify-between text-red-600 mt-2">
              <span>Remaining Balance:</span>
              <span>₱{{ formatAmount(receiptData?.remaining_balance) }}</span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm">
          <p>Thank you for your payment!</p>
          <p class="text-xs text-gray-500 mt-2">Keep this receipt for your records.</p>
        </div>
      </div>

      <!-- Modal Actions (visible only on screen) -->
      <div class="flex justify-end gap-4 mt-6 no-print">
        <button
          @click="handlePrint"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
        >
          Print Receipt
        </button>
        <button
          @click="closeModal"
          class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition-colors"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue'

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true
  },
  receiptData: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])
const receiptRef = ref(null)

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatAmount = (amount) => {
  return parseFloat(amount || 0).toFixed(2)
}

const handlePrint = () => {
  window.print()
}

const closeModal = () => {
  emit('close')
}
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }

  .print-content {
    padding: 20px;
  }

  /* Reset background colors for printing */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}

/* Ensure the receipt fits nicely on a standard receipt paper */
@page {
  size: 80mm 297mm;
  margin: 0;
}
</style>