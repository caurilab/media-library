<template>
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="media" class="modal-overlay" @click="handleOverlayClick">
          <div class="modal-container" @click.stop>
            <div class="modal-header">
              <h3 class="modal-title">{{ media.name }}</h3>
              <button @click="$emit('close')" class="modal-close">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>
  
            <div class="modal-body">
              <div class="media-preview-large">
                <img 
                  v-if="media.type === 'image'"
                  :src="media.url"
                  :alt="media.name"
                  class="preview-image"
                  @load="imageLoaded = true"
                  @error="imageError = true"
                >
                <div v-if="!imageLoaded && media.type === 'image'" class="image-loading">
                  <div class="loading-spinner"></div>
                </div>
                <div v-if="imageError && media.type === 'image'" class="image-error">
                  <PhotoIcon class="w-16 h-16 text-gray-400" />
                  <p>Impossible de charger l'image</p>
                </div>
                
                <video 
                  v-else-if="media.type === 'video'"
                  :src="media.url"
                  controls
                  class="preview-video"
                  preload="metadata"
                >
                  Votre navigateur ne supporte pas la lecture vidéo.
                </video>
                
                <audio 
                  v-else-if="media.type === 'audio'"
                  :src="media.url"
                  controls
                  class="preview-audio"
                  preload="metadata"
                >
                  Votre navigateur ne supporte pas la lecture audio.
                </audio>
                
                <div v-else class="preview-file">
                  <component :is="getTypeIcon(media.type)" class="w-16 h-16 text-gray-400 mb-4" />
                  <p class="text-lg font-medium text-gray-700">{{ media.name }}</p>
                  <a 
                    :href="media.url" 
                    target="_blank" 
                    class="download-link"
                    rel="noopener noreferrer"
                  >
                    Télécharger le fichier
                  </a>
                </div>
              </div>
  
              <div class="media-details">
                <div class="detail-section">
                  <h4 class="detail-title">Informations générales</h4>
                  <div class="detail-grid">
                    <div class="detail-item">
                      <span class="detail-label">Nom :</span>
                      <span class="detail-value">{{ media.name }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Nom du fichier :</span>
                      <span class="detail-value">{{ media.file_name }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Type :</span>
                      <span class="detail-value">{{ media.mime_type }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Taille :</span>
                      <span class="detail-value">{{ media.human_readable_size }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Collection :</span>
                      <span class="detail-value">{{ media.collection_name }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Créé le :</span>
                      <span class="detail-value">{{ formatDate(media.created_at) }}</span>
                    </div>
                  </div>
                </div>
  
                <div v-if="hasCustomProperties" class="detail-section">
                  <h4 class="detail-title">Propriétés personnalisées</h4>
                  <div class="detail-grid">
                    <div 
                      v-for="(value, key) in media.custom_properties" 
                      :key="key"
                      class="detail-item"
                    >
                      <span class="detail-label">{{ formatPropertyKey(key) }} :</span>
                      <span class="detail-value">{{ formatPropertyValue(value) }}</span>
                    </div>
                  </div>
                </div>
  
                <div class="detail-section">
                  <h4 class="detail-title">URLs</h4>
                  <div class="url-list">
                    <div class="url-item">
                      <span class="url-label">Original :</span>
                      <input 
                        :value="media.url" 
                        readonly 
                        class="url-input"
                        @click="selectText"
                      >
                      <button @click="copyToClipboard(media.url)" class="copy-button">
                        <DocumentDuplicateIcon v-if="!copied.original" class="w-4 h-4" />
                        <CheckIcon v-else class="w-4 h-4" />
                      </button>
                    </div>
                    <div v-if="media.urls.thumb" class="url-item">
                      <span class="url-label">Miniature :</span>
                      <input 
                        :value="media.urls.thumb" 
                        readonly 
                        class="url-input"
                        @click="selectText"
                      >
                      <button @click="copyToClipboard(media.urls.thumb, 'thumb')" class="copy-button">
                        <DocumentDuplicateIcon v-if="!copied.thumb" class="w-4 h-4" />
                        <CheckIcon v-else class="w-4 h-4" />
                      </button>
                    </div>
                    <div v-if="media.urls.medium" class="url-item">
                      <span class="url-label">Moyenne :</span>
                      <input 
                        :value="media.urls.medium" 
                        readonly 
                        class="url-input"
                        @click="selectText"
                      >
                      <button @click="copyToClipboard(media.urls.medium, 'medium')" class="copy-button">
                        <DocumentDuplicateIcon v-if="!copied.medium" class="w-4 h-4" />
                        <CheckIcon v-else class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
  
            <div class="modal-footer">
              <div class="footer-left">
                <a 
                  :href="media.url" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="action-button secondary"
                >
                  <ArrowTopRightOnSquareIcon class="w-4 h-4 mr-2" />
                  Ouvrir dans un nouvel onglet
                </a>
              </div>
              <div class="footer-right">
                <button @click="$emit('edit', media)" class="action-button primary">
                  <PencilIcon class="w-4 h-4 mr-2" />
                  Éditer
                </button>
                <button @click="handleDelete" class="action-button danger">
                  <TrashIcon class="w-4 h-4 mr-2" />
                  Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, watch, type Component } from 'vue'
  import { 
    XMarkIcon, 
    DocumentIcon, 
    FilmIcon, 
    MusicalNoteIcon, 
    ArchiveBoxIcon,
    PhotoIcon,
    PencilIcon,
    TrashIcon,
    ArrowTopRightOnSquareIcon,
    DocumentDuplicateIcon,
    CheckIcon
  } from '@heroicons/vue/24/outline'
  import type { MediaItem, MediaModalProps } from '../types'
  
  // Props avec types TypeScript
  const props = defineProps<MediaModalProps>()
  
  // Events avec types TypeScript
  interface Emits {
    close: []
    edit: [media: MediaItem]
    delete: [mediaId: number]
  }
  
  const emit = defineEmits<Emits>()
  
  // State
  const imageLoaded = ref<boolean>(false)
  const imageError = ref<boolean>(false)
  const copied = ref<Record<string, boolean>>({})
  
  // Computed
  const hasCustomProperties = computed(() => {
    return props.media?.custom_properties && 
           Object.keys(props.media.custom_properties).length > 0
  })
  
  // Methods
  const getTypeIcon = (type: string): Component => {
    const icons: Record<string, Component> = {
      video: FilmIcon,
      audio: MusicalNoteIcon,
      document: DocumentIcon,
      archive: ArchiveBoxIcon,
      pdf: DocumentIcon,
    }
    return icons[type] || DocumentIcon
  }
  
  const handleOverlayClick = (): void => {
    emit('close')
  }
  
  const handleDelete = (): void => {
    if (props.media && confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
      emit('delete', props.media.id)
      emit('close')
    }
  }
  
  const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  const formatPropertyKey = (key: string): string => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
  }
  
  const formatPropertyValue = (value: any): string => {
    if (typeof value === 'object') {
      return JSON.stringify(value, null, 2)
    }
    return String(value)
  }
  
  const selectText = (event: Event): void => {
    const target = event.target as HTMLInputElement
    target.select()
  }
  
  const copyToClipboard = async (text: string, type: string = 'original'): Promise<void> => {
    try {
      await navigator.clipboard.writeText(text)
      copied.value[type] = true
      
      // Reset after 2 seconds
      setTimeout(() => {
        copied.value[type] = false
      }, 2000)
    } catch (err) {
      console.error('Erreur lors de la copie:', err)
      // Fallback for older browsers
      const textArea = document.createElement('textarea')
      textArea.value = text
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      
      copied.value[type] = true
      setTimeout(() => {
        copied.value[type] = false
      }, 2000)
    }
  }
  
  // Watchers
  watch(() => props.media, () => {
    imageLoaded.value = false
    imageError.value = false
    copied.value = {}
  })
  
  // Keyboard shortcuts
  const handleKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
      emit('close')
    }
  }
  
  // Add/remove event listeners
  watch(() => props.media, (newMedia) => {
    if (newMedia) {
      document.addEventListener('keydown', handleKeydown)
    } else {
      document.removeEventListener('keydown', handleKeydown)
    }
  })
  </script>
  
  <style scoped>
  .modal-overlay {
    @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50;
  }
  
  .modal-container {
    @apply bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col;
  }
  
  .modal-header {
    @apply flex justify-between items-center p-6 border-b border-gray-200;
  }
  
  .modal-title {
    @apply text-xl font-semibold text-gray-900 truncate mr-4;
  }
  
  .modal-close {
    @apply text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-md hover:bg-gray-100;
  }
  
  .modal-body {
    @apply flex-1 overflow-auto p-6;
  }
  
  .media-preview-large {
    @apply mb-6 text-center relative;
  }
  
  .preview-image {
    @apply max-w-full max-h-96 mx-auto rounded-lg shadow-md;
  }
  
  .preview-video {
    @apply max-w-full max-h-96 mx-auto rounded-lg shadow-md;
  }
  
  .preview-audio {
    @apply w-full max-w-md mx-auto;
  }
  
  .preview-file {
    @apply flex flex-col items-center py-8;
  }
  
  .download-link {
    @apply text-blue-500 hover:text-blue-600 underline mt-2 inline-flex items-center;
  }
  
  .image-loading {
    @apply flex items-center justify-center h-96;
  }
  
  .image-error {
    @apply flex flex-col items-center justify-center h-96 text-gray-500;
  }
  
  .loading-spinner {
    @apply w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin;
  }
  
  .media-details {
    @apply space-y-6;
  }
  
  .detail-section {
    @apply border-b border-gray-200 pb-4 last:border-b-0;
  }
  
  .detail-title {
    @apply text-lg font-medium text-gray-900 mb-3;
  }
  
  .detail-grid {
    @apply grid grid-cols-1 md:grid-cols-2 gap-3;
  }
  
  .detail-item {
    @apply flex flex-col sm:flex-row sm:justify-between;
  }
  
  .detail-label {
    @apply text-sm font-medium text-gray-600 mb-1 sm:mb-0;
  }
  
  .detail-value {
    @apply text-sm text-gray-900 break-all;
  }
  
  .url-list {
    @apply space-y-3;
  }
  
  .url-item {
    @apply flex items-center space-x-2;
  }
  
  .url-label {
    @apply text-sm font-medium text-gray-600 w-20 flex-shrink-0;
  }
  
  .url-input {
    @apply flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500;
  }
  
  .copy-button {
    @apply p-2 text-gray-600 hover:text-blue-600 rounded-md hover:bg-gray-100 transition-colors duration-200;
  }
  
  .modal-footer {
    @apply flex justify-between items-center p-6 border-t border-gray-200;
  }
  
  .footer-left, .footer-right {
    @apply flex space-x-2;
  }
  
  .action-button {
    @apply inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200;
  }
  
  .action-button.primary {
    @apply bg-blue-500 text-white hover:bg-blue-600;
  }
  
  .action-button.secondary {
    @apply bg-gray-500 text-white hover:bg-gray-600;
  }
  
  .action-button.danger {
    @apply bg-red-500 text-white hover:bg-red-600;
  }
  
  /* Transitions */
  .modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
  }
  
  .modal-enter-from, .modal-leave-to {
    opacity: 0;
  }
  
  .modal-enter-active .modal-container,
  .modal-leave-active .modal-container {
    transition: transform 0.3s ease;
  }
  
  .modal-enter-from .modal-container,
  .modal-leave-to .modal-container {
    transform: scale(0.9);
  }
  </style>