export const useNotifications = () => {
  const showSuccess = (message) => {
    // You can replace this with a more sophisticated notification system later
    alert(message)
  }

  const showError = (message) => {
    // You can replace this with a more sophisticated notification system later
    alert(message)
  }

  return {
    showSuccess,
    showError
  }
}
