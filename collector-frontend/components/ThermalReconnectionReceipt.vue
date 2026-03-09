<template>
  <div id="thermal-reconnection-receipt" class="thermal-receipt">
    <!-- Header -->
    <div class="text-center mb-2">
      <div class="text-lg font-bold">CHARMAFA</div>
      <div class="text-xs">Water Association</div>
      <div class="text-xs">{{ formatDate(new Date()) }}</div>
      <div class="border-bottom"></div>
    </div>    <!-- Receipt Title -->
    <div class="text-center text-md font-bold mb-2">RECONNECTION RECEIPT</div>

    <!-- Copy Type -->
    <div v-if="receipt.copyType" class="text-center mb-2">
      <div class="copy-type-box text-xs">
        {{ receipt.copyType }}
      </div>
    </div>

    <!-- Member Info -->
    <div class="mb-2">
      <div class="row">
        <span class="label">Account No:</span>
        <span class="value">{{ receipt.member?.account_no }}</span>
      </div>
      <div class="row">
        <span class="label">Name:</span>
        <span class="value">{{ receipt.member?.fname }} {{ receipt.member?.lname }}</span>
      </div>
      <div class="row">
        <span class="label">Meter No:</span>
        <span class="value">{{ receipt.member?.meter_no }}</span>
      </div>      <div class="row">
        <span class="label">INVOICE Number:</span>
        <span class="value">{{ receipt.or_number || 'N/A' }}</span>
      </div>
    </div>

    <div class="border-bottom"></div>

    <!-- Reconnection Details -->
    <div class="mb-2">
      <div class="row">
        <span class="label">Reconnection Fee:</span>
        <span class="value">₱{{ formatMoney(receipt.reconnection_fee) }}</span>
      </div>
      <div v-if="receipt.prev_balance_paid > 0" class="row">
        <span class="label">Previous Balance:</span>
        <span class="value">₱{{ formatMoney(receipt.prev_balance_paid) }}</span>
      </div>
      
      <div class="border-bottom-thin"></div>
      
      <div class="row bold">
        <span class="label">TOTAL BILL:</span>
        <span class="value">₱{{ formatMoney(receipt.total_amount) }}</span>
      </div>
      
      <div class="border-bottom-thin"></div>
      
      <div class="row">
        <span class="label">Cash Received:</span>
        <span class="value">₱{{ formatMoney(receipt.cash) }}</span>
      </div>
      <div class="row bold">
        <span class="label">Change:</span>
        <span class="value">₱{{ formatMoney(receipt.change || 0) }}</span>
      </div>
      
      <div v-if="receipt.remaining_balance > 0" class="row">
        <span class="label">Remaining Balance:</span>
        <span class="value">₱{{ formatMoney(receipt.remaining_balance) }}</span>
      </div>
    </div>

    <div class="border-bottom"></div>

    <!-- Status -->
    <div class="text-center text-sm font-bold mb-2">
      ✓ CONNECTION RESTORED
    </div>

    <div class="border-bottom"></div>

    <!-- Footer -->
    <div class="text-center text-xs mb-2">
      <div>Thank you for your payment!</div>
      <div>Connection has been restored</div>
      <div>Keep this receipt for your records</div>
    </div>

    <div class="border-bottom"></div>

    <div class="text-center text-xs">
      <div>Processed by: {{ receipt.processed_by }}</div>
      <div>Official Receipt</div>
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
</script>

<style scoped>
.thermal-receipt {
  width: 80mm;
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

.text-sm {
  font-size: 11px;
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
  border-bottom: 1px dashed #000;
  margin: 8px 0;
}

.border-bottom-thin {
  border-bottom: 1px solid #ccc;
  margin: 4px 0;
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
  flex: 0 0 50%;
}

.value {
  flex: 0 0 45%;
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