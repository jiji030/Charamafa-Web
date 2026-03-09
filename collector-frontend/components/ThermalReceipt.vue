<!-- components/ThermalReceipt.vue -->

<template>
  <div id="thermal-receipt" class="thermal-receipt">    <!-- Header -->
    <div class="text-center mb-2">
      <div class="text-md font-bold">INVOICE</div>
      <div class="text-lg font-bold">CHARMAFA</div>
      <div class="text-xs">Purok-2, Charito, Bayugan City,</div>
      <div class="text-xs">Agusan del Sur</div>
      <div class="text-xs">TIN #: _______________</div>
      <div class="border-bottom mt-1"></div>
    </div><!-- Invoice Number and Date -->
    <div class="mb-2">
      <div class="row">
        <span class="label">INVOICE No:</span>
        <span class="value">{{ getDisplayOrNumber(receipt.or_number) }}</span>
      </div>
      <div class="row">
        <span class="label">Date Issued:</span>
        <span class="value">{{ formatDate(new Date()) }}</span>
      </div>
    </div><!-- Copy Type -->
    <div v-if="receipt.copyType" class="text-center mb-2">
      <div class="copy-type-box text-xs">
        {{ receipt.copyType }}
      </div>
    </div>

    <!-- Member Info -->
    <div class="mb-2">
      <div class="row">
        <span class="label">Name:</span>
        <span class="value">{{ receipt.member?.name }}</span>
      </div>
      <div class="row">
        <span class="label">Address:</span>
        <span class="value">{{ receipt.member?.address || 'Charito, Bayugan City' }}</span>
      </div>
    </div>

    <div class="border-dashed mb-2"></div>

    <!-- Bill Details -->
    <div class="mb-2">
      <div class="row">
        <span class="label">Water Charges</span>
        <span class="value">₱{{ formatMoney(receipt.water_charges || receipt.total_bill) }}</span>
      </div>
      <div class="row">
        <span class="label">Balance</span>
        <span class="value">₱{{ formatMoney(receipt.prev_balance || 0) }}</span>
      </div>
      <div class="row">
        <span class="label">Penalty</span>
        <span class="value">₱{{ formatMoney(receipt.penalty || 0) }}</span>
      </div>
      <div v-if="receipt.other_fees && receipt.other_fees > 0" class="row">
        <span class="label">Other Fees: {{ receipt.other_fees_description || 'Misc' }}</span>
        <span class="value">₱{{ formatMoney(receipt.other_fees) }}</span>
      </div>
    </div>

    <div class="border-dashed mb-2"></div>

    <!-- Total Amount -->
    <div class="mb-2">
      <div class="row total-row">
        <span class="label font-bold">TOTAL AMOUNT PAID</span>
        <span class="value font-bold">₱{{ formatMoney(receipt.amount_paid || receipt.cash) }}</span>
      </div>
    </div>    <div class="border-dashed mb-2"></div>

    <!-- Received By -->
    <div class="mb-2">
      <div class="row">
        <span class="label">Received By:</span>
        <span class="value">{{ receipt.processed_by }}</span>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs mt-3">
      <div class="text-xs italic">This Official Receipt is valid only for</div>
      <div class="text-xs italic">the purpose indicated above.</div>
    </div>

    <!-- Spacing for tear-off -->
    <div class="spacer"></div>
  </div>
</template>

<script setup>
const props = defineProps({
  receipt: {
    type: Object,
    required: true
  }
})

const formatDate = (date) => {
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const formatMoney = (amount) => {
  return Number(amount).toFixed(2)
}

const getDisplayOrNumber = (orNumber) => {
  if (!orNumber) return '___________'
  
  // If it's a temporary INVOICE number (starts with NO-INV-), show a formatted version
  if (orNumber.startsWith('NO-INV-')) {
    return 'TEMP-' + orNumber.split('-').slice(-1)[0] // Show TEMP-timestamp
  }
  
  return orNumber
}
</script>

<style scoped>
.thermal-receipt {
  width: 80mm; /* Standard thermal receipt width */
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.4;
  padding: 5mm;
  background: white;
  color: black;
}

.text-center {
  text-align: center;
}

.text-xs {
  font-size: 10px;
}

.text-md {
  font-size: 14px;
}

.text-lg {
  font-size: 16px;
}

.font-bold {
  font-weight: bold;
}

.mb-2 {
  margin-bottom: 8px;
}

.border-bottom {
  border-bottom: 1px solid #000;
  margin: 8px 0;
}

.border-dashed {
  border-bottom: 1px dashed #000;
  margin: 8px 0;
}

.mt-1 {
  margin-top: 4px;
}

.mt-3 {
  margin-top: 12px;
}

.italic {
  font-style: italic;
}

.row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.row.bold {
  font-weight: bold;
  font-size: 13px;
}

.label {
  flex: 0 0 45%;
}

.value {
  flex: 0 0 50%;
  text-align: right;
}

.spacer {
  height: 20mm;
}

.copy-type-box {
  border: 2px solid #000;
  padding: 4px 8px;
  margin: 4px auto;
  display: inline-block;
  font-weight: bold;
  background: #f0f0f0;
}

/* Print styles */
@media print {
  .thermal-receipt {
    width: 80mm;
    margin: 0;
    padding: 5mm;
  }
  
  @page {
    size: 80mm auto;
    margin: 0;
  }
}
</style>