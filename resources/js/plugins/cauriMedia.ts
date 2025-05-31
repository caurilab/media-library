import type { App } from 'vue'
import type { MediaConfig } from '../types'

// Plugin pour Vue.js/Nuxt.js
export interface CauriMediaOptions {
  apiBaseUrl?: string
  maxFileSize?: number
  allowedMimeTypes?: string[]
  defaultCollection?: string
  csrfToken?: string
}

export default {
  install(app: App, options: CauriMediaOptions = {}) {
    const config: MediaConfig = {
      apiBaseUrl: options.apiBaseUrl || '/api/cauri-media',
      maxFileSize: options.maxFileSize || 50 * 1024 * 1024, // 50MB
      allowedMimeTypes: options.allowedMimeTypes || [],
      defaultCollection: options.defaultCollection || 'default',
      conversions: {}
    }

    // Provide global config
    app.provide('cauriMediaConfig', config)

    // Global properties
    app.config.globalProperties.$cauriMedia = config

    // Set CSRF token if provided
    if (options.csrfToken) {
      const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
      if (meta) {
        meta.content = options.csrfToken
      }
    }
  }
}

// Composable pour accéder à la config
export function useCauriMediaConfig(): MediaConfig {
  const { $cauriMedia } = getCurrentInstance()?.appContext.config.globalProperties || {}
  return $cauriMedia
}