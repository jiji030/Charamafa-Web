<!-- DefectMeterModal.vue -->
<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black opacity-50"></div>
      
      <!-- Modal content -->
      <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <!-- Close button -->
        <button 
          @click="$emit('response', null)" 
          class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        
        <!-- Header -->
        <div class="mb-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">
            Payment Type Selection
          </h3>
          <p class="text-gray-600">
            Please select the appropriate payment type for this member:
          </p>
        </div>

        <!-- Payment Type Options -->
        <div class="space-y-4">
          <!-- Regular Payment Option -->
          <div class="border-2 border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
            <button
              @click="$emit('response', { type: 'regular', isDefective: false })"
              class="w-full text-left"
            >
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-1">
                  <div class="w-4 h-4 rounded-full bg-blue-500 flex items-center justify-center">
                    <div class="w-2 h-2 rounded-full bg-white"></div>
                  </div>
                </div>
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900">Regular Payment</h4>
                  <p class="text-sm text-gray-600 mt-1">
                    Normal billing payment (full or partial due to insufficient funds)
                  </p>
                  <p class="text-xs text-blue-600 mt-2 font-medium">
                    ✓ Water consumption will be maintained
                  </p>
                </div>
              </div>
            </button>
          </div>

          <!-- Defective Meter Option -->
          <div class="border-2 border-yellow-300 rounded-lg p-4 bg-yellow-50 hover:border-yellow-400 transition-colors">
            <button
              @click="$emit('response', { type: 'defective', isDefective: true })"
              class="w-full text-left"
            >
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-1">
                  <div class="w-4 h-4 rounded-full bg-yellow-500 flex items-center justify-center">
                    <div class="w-2 h-2 rounded-full bg-white"></div>
                  </div>
                </div>
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900">Defective Meter Payment</h4>
                  <p class="text-sm text-gray-600 mt-1">
                    Payment for member with replaced/defective water meter
                  </p>
                  <p class="text-xs text-yellow-700 mt-2 font-medium">
                    ⚠️ Water consumption will be reset to zero
                  </p>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-6 p-3 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-600">
            <strong>Note:</strong> Choose "Defective Meter Payment" only when the water meter has been physically replaced or repaired, requiring consumption reset.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

defineEmits(['response'])
</script>
