// composables/useAppInit.js
import { ref } from 'vue'
import { B24AuthSDK, useAuthStore } from '@payment-app/authSdk'
import bitrixService from '../services/bitrixService'

export function useAppInit() {
  const authStore = useAuthStore()
  const isLoading = ref(false)
  const error = ref(null)

  const initialize = async () => {
    
    if (!bitrixService.isInitialized)
      await bitrixService.init()
    if (authStore.isInitialized) return


    isLoading.value = true
    error.value = null
    try {
      const sdk = new B24AuthSDK({
        baseURL: import.meta.env.VITE_API_URL,
        appId: import.meta.env.VITE_APP_ID,
        auth: bitrixService.appData.auth
      })

      console.log(bitrixService.appData.auth)
      const store = await sdk.createStore()

      if (!store.isAuthenticated) {
        const result = await store.login()
        if (!result.success) {
          const registerResult = await store.register();
          if (!registerResult.success) {
            throw new Error(registerResult.message || 'Ошибка авторизации')
          }
        }
      }

      return store
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    initialize,
    isLoading,
    error
  }
}