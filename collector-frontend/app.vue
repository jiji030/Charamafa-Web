<template>
  <div>
    <NuxtPage />
  </div>
</template>

<script setup>
const authStore = useAuthStore()

// Initialize auth on app mount
onMounted(() => {
  if (process.client) {
    authStore.initAuth()
  }
})
</script>

<style>
@media print {
  .no-print {
    display: none !important;
  }
  
  .print-only {
    display: block !important;
  }
  
  body {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}

.print-only {
  display: none;
}
</style>