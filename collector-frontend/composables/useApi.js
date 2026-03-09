export const useApi = () => {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()

  // Dynamic API base URL detection
  const getApiBaseURL = () => {
    // Use configured base URL from runtime config
    if (config.public.apiBase) {
      return config.public.apiBase
    }

    // Fallback: Auto-detect based on current location (client-side only)
    if (process.client) {
      const currentHost = window.location.hostname
      const protocol = window.location.protocol
      return `${protocol}//${currentHost}:8000/api`
    }

    // Server-side fallback
    return 'https://localhost:8000/api'
  }

  const api = (request, opts = {}) => {
    // If body is FormData, remove Content-Type so browser sets it
    const isFormData = opts.body instanceof FormData
    const headers = {
      'Accept': 'application/json',
      ...(isFormData ? {} : { 'Content-Type': 'application/json' })
    }

    // Only add Authorization header if token exists
    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`
    }

    const baseURL = getApiBaseURL()

    return $fetch(request, { 
      ...opts, 
      baseURL, 
      headers: {
        ...headers,
        ...(opts.headers || {})
      }
    })
  }

  return api
}