export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()
  
  // Initialize auth from localStorage on client side
  if (process.client) {
    authStore.initAuth()
  }
  
  // Public route - allow access
  if (to.path === '/') {
    // If already authenticated, redirect to appropriate dashboard
    if (authStore.isAuthenticated) {
      const roleId = authStore.user?.role_id
        if (roleId === 3) { // Collector
        return navigateTo('/collector/dashboard')
      } else if (roleId === 2) { // Treasurer
        return navigateTo('/treasurer/master-list')
      } else if (roleId === 1) { // Admin/President
        return navigateTo('/president/dashboard')
      }
    }
    return
  }
  
  // Protected routes - check authentication
  if (!authStore.isAuthenticated) {
    return navigateTo('/')
  }
  
  // Role-based access control
  const roleId = authStore.user?.role_id
  const path = to.path
  
  // Collector routes (role_id = 3)
  if (path.startsWith('/collector')) {
    if (roleId !== 3) {
      return navigateTo('/')
    }
  }
    // Treasurer routes (role_id = 2)
  if (path.startsWith('/treasurer')) {
    if (roleId !== 2) {
      return navigateTo('/')
    }
  }
  
  // President routes (role_id = 1)
  if (path.startsWith('/president')) {
    if (roleId !== 1) {
      return navigateTo('/')
    }
  }
  
  // Admin routes (role_id = 1)
  if (path.startsWith('/admin')) {
    if (roleId !== 1) {
      return navigateTo('/')
    }
  }
})