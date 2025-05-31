// Point d'entrée principal pour CAURI Media Library

// Export des types
export * from './types'

// Export des composables
export { useMediaUpload } from './composables/useMediaUpload'
export { useMediaGallery } from './composables/useMediaGallery'

// Export des composants
export { default as MediaUploader } from './components/MediaUploader.vue'
export { default as MediaItem } from './components/MediaItem.vue'
export { default as MediaGallery } from './components/MediaGallery.vue'
export { default as MediaModal } from './components/MediaModal.vue'

// Export des utilitaires
export * as mediaHelpers from './utils/mediaHelpers'

// Export du plugin
export { default as CauriMediaPlugin } from './plugins/cauriMedia'

// Configuration par défaut
export const DEFAULT_CONFIG = {
  maxFileSize: 50 * 1024 * 1024, // 50MB
  allowedMimeTypes: [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp',
    'video/mp4',
    'audio/mp3',
    'application/pdf'
  ],
  defaultCollection: 'default',
  apiBaseUrl: '/api/cauri-media'
}