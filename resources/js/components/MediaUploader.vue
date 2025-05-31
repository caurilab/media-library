<template>
    <div class="cauri-media-uploader">
      <!-- Zone de Upload -->
      <div 
        @drop="handleDrop"
        @dragover.prevent
        @dragenter.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        class="upload-zone"
        :class="{
          'drag-over': isDragging,
          'uploading': uploading,
          'has-error': hasErrors,
          'is-success': isComplete
        }"
      >
        <input 
          ref="fileInput"
          type="file"
          :multiple="multiple"
          :accept="acceptedTypes"
          @change="handleFileSelect"
          class="hidden"
        >
        
        <div v-if="!uploading" @click="openFileDialog" class="upload-content">
          <div class="upload-icon">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
              />
            </svg>
          </div>
          <h3 class="upload-title">{{ dragTitle }}</h3>
          <p class="upload-subtitle">{{ dragSubtitle }}</p>
          <button type="button" class="upload-button">
            Choisir des fichiers
          </button>
        </div>
        
        <div v-else class="upload-progress">
          <div class="progress-circle">
            <svg class="w-16 h-16" viewBox="0 0 100 100">
              <circle 
                cx="50" cy="50" r="40" 
                fill="none" stroke="#e5e7eb" stroke-width="8"
              />
              <circle 
                cx="50" cy="50" r="40" 
                fill="none" stroke="#3b82f6" stroke-width="8"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
                transform="rotate(-90 50 50)"
                class="transition-all duration-300"
              />
            </svg>
            <div class="progress-text">
              {{ Math.round(progress) }}%
            </div>
          </div>
          <p class="upload-status">{{ uploadStatus }}</p>
        </div>
      </div>
  
      <!-- Erreurs -->
      <Transition name="fade">
        <div v-if="hasErrors" class="error-container">
          <div class="error-header">
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path 
                fill-rule="evenodd" 
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" 
                clip-rule="evenodd" 
              />
            </svg>
            <span class="error-title">Erreurs d'upload</span>
          </div>
          <ul class="error-list">
            <li v-for="error in errors" :key="error" class="error-item">
              {{ error }}
            </li>
          </ul>
          <button @click="clearErrors" class="error-dismiss">
            Fermer
          </button>
        </div>
      </Transition>
  
      <!-- Fichiers uploadés -->
      <Transition name="slide-up">
        <div v-if="uploadedFiles.length > 0" class="uploaded-files">
          <h4 class="uploaded-title">
            Fichiers uploadés ({{ uploadedFiles.length }})
          </h4>
          <div class="uploaded-grid">
            <MediaItem 
              v-for="file in uploadedFiles" 
              :key="file.id"
              :media="file"
              :show-actions="true"
              @delete="handleFileDelete"
              @edit="handleFileEdit"
            />
          </div>
        </div>
      </Transition>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, watch, type Ref } from 'vue'
  import { useMediaUpload } from '../composables/useMediaUpload'
  import MediaItem from './MediaItem.vue'
  import type { MediaItem as MediaItemType, MediaUploadOptions } from '../types'
  
  // Props avec types TypeScript
  interface Props {
    modelType?: string
    modelId?: number | string
    collection?: string
    multiple?: boolean
    acceptedTypes?: string
    maxSize?: number
    maxFiles?: number
    dragTitle?: string
    dragSubtitle?: string
    autoUpload?: boolean
  }
  
  const props = withDefaults(defineProps<Props>(), {
    collection: 'default',
    multiple: true,
    maxSize: 50 * 1024 * 1024, // 50MB
    maxFiles: 20,
    dragTitle: 'Glissez vos fichiers ici',
    dragSubtitle: 'ou cliquez pour sélectionner',
    autoUpload: true,
  })
  
  // Events avec types TypeScript
  interface Emits {
    uploaded: [result: any]
    error: [error: Error]
    fileDeleted: [mediaId: number]
    fileUpdated: [media: MediaItemType]
  }
  
  const emit = defineEmits<Emits>()
  
  // State
  const isDragging = ref<boolean>(false)
  const fileInput: Ref<HTMLInputElement | null> = ref(null)
  
  // Composable
  const { 
    uploadFiles, 
    deleteFile, 
    progress, 
    uploading, 
    errors, 
    uploadedFiles,
    hasErrors,
    clearErrors,
    reset,
    status,
    isComplete
  } = useMediaUpload()
  
  // Computed
  const circumference = computed(() => 2 * Math.PI * 40)
  const dashOffset = computed(() => circumference.value - (progress.value / 100) * circumference.value)
  
  const uploadStatus = computed(() => {
    if (uploading.value) {
      if (progress.value < 100) {
        return `Upload en cours... ${Math.round(progress.value)}%`
      }
      return 'Traitement en cours...'
    }
    return ''
  })
  
  // Methods
  const openFileDialog = (): void => {
    fileInput.value?.click()
  }
  
  const handleDrop = (e: DragEvent): void => {
    e.preventDefault()
    isDragging.value = false
    
    const files = Array.from(e.dataTransfer?.files || [])
    processFiles(files)
  }
  
  const handleFileSelect = (e: Event): void => {
    const target = e.target as HTMLInputElement
    const files = Array.from(target.files || [])
    processFiles(files)
    
    // Reset input
    target.value = ''
  }
  
  const processFiles = async (files: File[]): Promise<void> => {
    if (!files.length) return
  
    // Validation côté client
    const validationErrors = validateFiles(files)
    if (validationErrors.length > 0) {
      errors.value = validationErrors
      return
    }
  
    if (props.autoUpload) {
      await uploadFilesNow(files)
    }
  }
  
  const validateFiles = (files: File[]): string[] => {
    const errors: string[] = []
    
    if (files.length > props.maxFiles) {
      errors.push(`Maximum ${props.maxFiles} fichiers autorisés`)
    }
  
    files.forEach((file, index) => {
      if (file.size > props.maxSize) {
        errors.push(`Fichier ${index + 1}: Taille maximale ${formatFileSize(props.maxSize)} dépassée`)
      }
  
      if (props.acceptedTypes) {
        const allowedTypes = props.acceptedTypes.split(',').map(type => type.trim())
        const fileType = file.type
        const isAllowed = allowedTypes.some(type => {
          if (type.endsWith('/*')) {
            return fileType.startsWith(type.slice(0, -1))
          }
          return fileType === type
        })
        
        if (!isAllowed) {
          errors.push(`Fichier ${index + 1}: Type non autorisé (${file.type})`)
        }
      }
    })
  
    return errors
  }
  
  const uploadFilesNow = async (files: File[]): Promise<void> => {
    try {
      const options: MediaUploadOptions = {
        modelType: props.modelType,
        modelId: props.modelId,
        collection: props.collection,
      }
      
      const result = await uploadFiles(files, options)
      emit('uploaded', result)
    } catch (error) {
      emit('error', error as Error)
    }
  }
  
  const handleFileDelete = async (mediaId: number): Promise<void> => {
    try {
      await deleteFile(mediaId)
      emit('fileDeleted', mediaId)
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
    }
  }
  
  const handleFileEdit = (media: MediaItemType): void => {
    emit('fileUpdated', media)
  }
  
  const formatFileSize = (bytes: number): string => {
    const sizes = ['B', 'KB', 'MB', 'GB']
    if (bytes === 0) return '0 B'
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
  }
  
  // Watchers
  watch(() => props.collection, () => {
    reset()
  })
  
  // Expose methods for parent components
  defineExpose({
    uploadFiles: uploadFilesNow,
    reset,
    clearErrors,
  })
  </script>
  
  <style scoped>
  .cauri-media-uploader {
    @apply w-full;
  }
  
  .upload-zone {
    @apply border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-all duration-200 cursor-pointer;
  }
  
  .upload-zone:hover {
    @apply border-gray-400 bg-gray-50;
  }
  
  .upload-zone.drag-over {
    @apply border-blue-500 bg-blue-50;
  }
  
  .upload-zone.uploading {
    @apply cursor-not-allowed;
  }
  
  .upload-zone.has-error {
    @apply border-red-300 bg-red-50;
  }
  
  .upload-zone.is-success {
    @apply border-green-300 bg-green-50;
  }
  
  .upload-content {
    @apply space-y-4;
  }
  
  .upload-icon {
    @apply flex justify-center;
  }
  
  .upload-title {
    @apply text-lg font-medium text-gray-900;
  }
  
  .upload-subtitle {
    @apply text-sm text-gray-500;
  }
  
  .upload-button {
    @apply bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200;
  }
  
  .upload-progress {
    @apply flex flex-col items-center space-y-4;
  }
  
  .progress-circle {
    @apply relative;
  }
  
  .progress-text {
    @apply absolute inset-0 flex items-center justify-center text-lg font-bold text-blue-600;
  }
  
  .upload-status {
    @apply text-sm text-gray-600;
  }
  
  .error-container {
    @apply mt-4 bg-red-50 border border-red-200 rounded-lg p-4;
  }
  
  .error-header {
    @apply flex items-center space-x-2 mb-2;
  }
  
  .error-title {
    @apply text-sm font-medium text-red-800;
  }
  
  .error-list {
    @apply list-disc list-inside space-y-1 text-sm text-red-700 mb-3;
  }
  
  .error-item {
    @apply ml-4;
  }
  
  .error-dismiss {
    @apply text-xs text-red-600 hover:text-red-800 underline;
  }
  
  .uploaded-files {
    @apply mt-6;
  }
  
  .uploaded-title {
    @apply text-lg font-medium text-gray-900 mb-4;
  }
  
  .uploaded-grid {
    @apply grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4;
  }
  
  /* Transitions */
  .fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
  }
  
  .fade-enter-from, .fade-leave-to {
    opacity: 0;
  }
  
  .slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.3s ease;
  }
  
  .slide-up-enter-from, .slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
  }
  </style>