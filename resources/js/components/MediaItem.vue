<template>
    <div 
      class="media-item" 
      :class="{ 
        'selected': isSelected,
        'loading': isLoading 
      }"
    >
      <div class="media-preview" @click="handleClick">
        <div class="media-thumbnail">
          <img 
            v-if="media.type === 'image'" 
            :src="media.urls.thumb || media.url"
            :alt="media.name"
            class="thumbnail-image"
            loading="lazy"
            @error="handleImageError"
          >
          <div v-else class="thumbnail-placeholder">
            <component 
              :is="getTypeIcon(media.type)" 
              class="w-8 h-8 text-gray-400" 
            />
          </div>
        </div>
        
        <!-- Overlay avec actions -->
        <Transition name="fade">
          <div v-if="showActions && !isLoading" class="media-overlay">
            <div class="media-actions">
              <button 
                @click.stop="$emit('preview', media)"
                class="action-button preview-button"
                title="Aperçu"
              >
                <EyeIcon class="w-4 h-4" />
              </button>
              <button 
                @click.stop="$emit('edit', media)"
                class="action-button edit-button"
                title="Éditer"
              >
                <PencilIcon class="w-4 h-4" />
              </button>
              <button 
                @click.stop="handleDelete"
                class="action-button delete-button"
                title="Supprimer"
                :disabled="isDeleting"
              >
                <TrashIcon v-if="!isDeleting" class="w-4 h-4" />
                <div v-else class="w-4 h-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
              </button>
            </div>
          </div>
        </Transition>
  
        <!-- Badge de type -->
        <div class="media-badge">
          {{ media.extension?.toUpperCase() }}
        </div>
  
        <!-- Indicateur de sélection -->
        <div v-if="selectable" class="selection-indicator">
          <input 
            type="checkbox" 
            :checked="isSelected"
            @change="handleSelectionChange"
            class="selection-checkbox"
          >
        </div>
  
        <!-- Loading overlay -->
        <Transition name="fade">
          <div v-if="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
          </div>
        </Transition>
      </div>
  
      <!-- Informations -->
      <div class="media-info">
        <h4 class="media-name" :title="media.name">
          {{ media.name }}
        </h4>
        <div class="media-meta">
          <span class="media-size">{{ media.human_readable_size }}</span>
          <span class="media-collection">{{ media.collection_name }}</span>
        </div>
        <div v-if="showTimestamp" class="media-timestamp">
          {{ formatDate(media.created_at) }}
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, type Component } from 'vue'
  import { 
    EyeIcon, 
    PencilIcon, 
    TrashIcon,
    DocumentIcon,
    FilmIcon,
    MusicalNoteIcon,
    ArchiveBoxIcon,
    PhotoIcon
  } from '@heroicons/vue/24/outline'
  import type { MediaItem } from '../types'
  
  // Props avec types TypeScript
  interface Props {
    media: MediaItem
    showActions?: boolean
    selectable?: boolean
    selected?: boolean
    showTimestamp?: boolean
  }
  
  const props = withDefaults(defineProps<Props>(), {
    showActions: false,
    selectable: false,
    selected: false,
    showTimestamp: false,
    isLoading: false,
    isDeleting: false,
    isSelected: false,
    
  })
  
  // Events avec types TypeScript
  interface Emits {
    click: [media: MediaItem]
    preview: [media: MediaItem]
    edit: [media: MediaItem]
    delete: [mediaId: number]
    select: [mediaId: number]
  }
  
  const emit = defineEmits<Emits>()
  
  // State
  const isDeleting = ref<boolean>(false)
  const isLoading = ref<boolean>(false)
  const imageError = ref<boolean>(false)
  
  // Computed
  const isSelected = computed(() => props.selected)
  
  // Methods
  const getTypeIcon = (type: string): Component => {
    const icons: Record<string, Component> = {
      video: FilmIcon,
      audio: MusicalNoteIcon,
      document: DocumentIcon,
      archive: ArchiveBoxIcon,
      pdf: DocumentIcon,
      image: PhotoIcon,
    }
    return icons[type] || DocumentIcon
  }
  
  const handleClick = (): void => {
    emit('click', props.media)
  }
  
  const handleDelete = async (): Promise<void> => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
      isDeleting.value = true
      try {
        emit('delete', props.media.id)
      } finally {
        isDeleting.value = false
      }
    }
  }
  
  const handleSelectionChange = (event: Event): void => {
    const target = event.target as HTMLInputElement
    if (target.checked) {
      emit('select', props.media.id)
    }
  }
  
  const handleImageError = (): void => {
    imageError.value = true
  }
  
  const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  }
  </script>
  
  <style scoped>
  .media-item {
    @apply bg-white rounded-lg shadow-sm overflow-hidden group hover:shadow-md transition-all duration-200;
  }
  
  .media-item.selected {
    @apply ring-2 ring-blue-500 shadow-lg;
  }
  
  .media-item.loading {
    @apply opacity-75;
  }
  
  .media-preview {
    @apply relative aspect-square bg-gray-100;
  }
  
  .media-thumbnail {
    @apply w-full h-full flex items-center justify-center;
  }
  
  .thumbnail-image {
    @apply w-full h-full object-cover;
  }
  
  .thumbnail-placeholder {
    @apply flex items-center justify-center w-full h-full;
  }
  
  .media-overlay {
    @apply absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100;
  }
  
  .media-actions {
    @apply flex space-x-2;
  }
  
  .action-button {
    @apply p-2 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full transition-all duration-200 transform hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed;
  }
  
  .preview-button {
    @apply text-blue-600 hover:text-blue-700;
  }
  
  .edit-button {
    @apply text-yellow-600 hover:text-yellow-700;
  }
  
  .delete-button {
    @apply text-red-600 hover:text-red-700;
  }
  
  .media-badge {
    @apply absolute top-2 right-2 bg-gray-900 bg-opacity-75 text-white text-xs px-2 py-1 rounded;
  }
  
  .selection-indicator {
    @apply absolute top-2 left-2;
  }
  
  .selection-checkbox {
    @apply w-4 h-4 text-blue-600 bg-white bg-opacity-90 border-gray-300 rounded focus:ring-blue-500;
  }
  
  .loading-overlay {
    @apply absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center;
  }
  
  .loading-spinner {
    @apply w-6 h-6 animate-spin rounded-full border-2 border-blue-500 border-t-transparent;
  }
  
  .media-info {
    @apply p-3;
  }
  
  .media-name {
    @apply font-medium text-sm text-gray-900 truncate mb-1;
  }
  
  .media-meta {
    @apply flex justify-between text-xs text-gray-500;
  }
  
  .media-size, .media-collection {
    @apply truncate;
  }
  
  .media-timestamp {
    @apply text-xs text-gray-400 mt-1;
  }
  
  /* Transitions */
  .fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s ease;
  }
  
  .fade-enter-from, .fade-leave-to {
    opacity: 0;
  }
  </style>