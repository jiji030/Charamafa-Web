<!-- components/ThermalPrinterSetup.vue -->

<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4" @click.stop>
      <h3 class="text-xl font-bold text-gray-800 mb-4">Thermal Printer Setup</h3>
      
      <div class="space-y-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <h4 class="font-semibold text-blue-900 mb-2">Steps to Use the Printer for Printing Receipt</h4>
          <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
            <li>I-on ang thermal bluetooth printer</li>
            <li>Mag process ug payment</li>
            <li>Human sa payment, pair ang printer gamit ang connect printer</li>
            <li>Pisliton ang print</li>
          </ul>
        </div>

        <!-- <div class="bg-green-50 p-4 rounded-lg">
          <h4 class="font-semibold text-green-900 mb-2">Step 2: Browser Print Settings</h4>
          <ul class="list-disc list-inside text-sm text-green-800 space-y-1">
            <li>When print dialog opens, select your thermal printer</li>
            <li>Set paper size to "80mm" or "58mm" (match your printer)</li>
            <li>Disable headers and footers</li>
            <li>Set margins to "None" or "Minimum"</li>
          </ul>
        </div> -->

        <!-- <div class="bg-yellow-50 p-4 rounded-lg">
          <h4 class="font-semibold text-yellow-900 mb-2">Troubleshooting</h4>
          <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1">
            <li>If receipt is cut off: Check paper size settings</li>
            <li>If text is too small/large: Adjust printer DPI settings</li>
            <li>If printer not found: Re-pair Bluetooth connection</li>
          </ul>
        </div> -->
      </div>

      <button
        @click="$emit('close')"
        class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
      >
        Got it!
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: Boolean
})

defineEmits(['close'])
</script>