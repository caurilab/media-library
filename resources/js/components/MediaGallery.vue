<template>
    <div class="media-gallery">
      <!-- Header avec filtres -->
      <div class="gallery-header">
        <div class="gallery-title">
          <h2 class="text-xl font-semibold">{{ title }}</h2>
          <p v-if="subtitle" class="text-sm text-gray-600">{{ subtitle }}</p>
        </div>
        
        <div class="gallery-filters">
          <div class="filter-group">
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Rechercher..."
              class="search-input"
              @input="debounceSearch"
            >
          </div>
          
          <div class="filter-group">
            <select v-model="selectedType" class="filter-select" @change="applyFilters">
              <option value="">Tous les types</option>
              <option value="image">Images</option>
              <option value="video">Vidéos</option>
              <option value="audio">Audio</option>
              <option value="document">Documents</option>
            </select>
          </div>
          
          <div class="filter-group">
            <select v-model="selectedCollection" class="filter-select" @change="applyFilters">
              <option value="">Toutes les collections</option>
              <option v-for="collection in collections" :key="collection.name" :value="collection.name">
                {{ collection.name }} ({{ collection.media_count }})
              </option>
            </select>
          </div>
  
          <div class="view-controls">
            <button 
              @click="viewMode = MediaViewMode.GRID"
              :class="['view-button', { active: viewMode === MediaViewMode.GRID }]"
              title="Vue grille"
            >
              <Squares2X2Icon class="w-4 h-4" />
            </button>
            <button 
              @click="viewMode = MediaViewMode.LIST"
              :class="['view-button', { active: viewMode === MediaViewMode.LIST }]"
              title="Vue liste"
            >
              <ListBulletIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
  
      <!-- Actions de sélection -->
      <Transition name="slide-down">
        <div v-if="selectable && selectedItems.length > 0" class="selection-actions">
          <div class="selection-info">
            {{ selectedItems.length }} élément(s) sélectionné(s)
          </div>
          <div class="selection-buttons">
            <button @click="selectAll" class="selection-button">
              Tout sélectionner
            </button>
            <button @click="clearSelection" class="selection-button">
              Désélectionner
            </button>
            <button @click="deleteSelected" class="selection-button danger" :disabled="isDeleting">
              <span v-if="!isDeleting">Supprimer</span>
              <span v-else class="flex items-center">
                <div class="w-4 h-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2"></div>
                Suppression...
              </span>
            </button>
          </div>
        </div>
      </Transition>
  
      <!-- Loading state -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Chargement...</p>
      </div>
  
      <!-- Gallery content -->
      <div v-else-if="displayedMedia.length > 0" :class="galleryClasses">
        <TransitionGroup name="media-list" tag="div" class="contents">
          <MediaItem
            v-for="media in paginatedMedia"
            :key="media.id"
            :media="media"
            :show-actions="showActions"
            :selectable="selectable"
            :selected="selectedItems.includes(media.id)"
            :show-timestamp="showTimestamp"
            @click="handleItemClick"
            @preview="handlePreview"
            @edit="handleEdit"
            @delete="handleDelete"
            @select="toggleSelection"
          />
        </TransitionGroup>
      </div>
  
      <!-- Empty state -->
      <div v-else class="empty-state">
        <div class="empty-icon">
          <FolderIcon class="w-16 h-16 text-gray-300" />
        </div>
        <h3 class="empty-title">Aucun média trouvé</h3>
        <p class="empty-subtitle">
          {{ getEmptyMessage() }}
        </p>
        <button v-if="hasFilters" @click="clearFilters" class="clear-filters-button">
          Effacer les filtres
        </button>
      </div>
  
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button 
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="pagination-button"
        >
          <ChevronLeftIcon class="w-4 h-4" />
          Précédent
        </button>
        
        <div class="pagination-pages">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="['pagination-page', { active: page === currentPage }]"
          >
            {{ page }}
          </button>
        </div>
        
        <div class="pagination-info">
          Page {{ currentPage }} sur {{ totalPages }}
        </div>
        
        <button 
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="pagination-button"
        >
          Suivant
          <ChevronRightIcon class="w-4 h-4" />
        </button>
      </div>
  
      <!-- Modal de prévisualisation -->
      <MediaModal 
        v-if="showModal"
        :media="selectedMedia"
        @close="closeModal"
        @edit="handleEdit"
        @delete="handleDelete"
      />
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, watch, onMounted, nextTick, type Ref } from 'vue'
  import { 
    Squares2X2Icon, 
    ListBulletIcon, 
    FolderIcon,
    ChevronLeftIcon,
    ChevronRightIcon
  } from '@heroicons/vue/24/outline'
  import { debounce } from 'lodash-es'
  import { useMediaGallery } from '../composables/useMediaGallery'
  import MediaItem from './MediaItem.vue'
  import MediaModal from './MediaModal.vue'
  import { MediaViewMode } from '../types'
  import type { MediaItem as MediaItemType, MediaGalleryProps } from '../types'
  
  // Props avec types TypeScript
  const props = withDefaults(defineProps<MediaGalleryProps>(), {
    title: 'Galerie média',
    showActions: true,
    selectable: false,
    showTimestamp: false,
    perPage: 24,
    autoLoad: true
  })
  
  // Events avec types TypeScript
  interface Emits {
    itemClick: [media: MediaItemType]
    preview: [media: MediaItemType]
    edit: [media: MediaItemType]
    delete: [mediaId: number]
    selectionChange: [selectedIds: number[], selectedItems: MediaItemType[]]
  }
  
  const emit = defineEmits<Emits>()
  
  // State
  const viewMode = ref<MediaViewMode>(MediaViewMode.GRID)
  const searchQuery = ref<string>('')
  const selectedType = ref<string>('')
  const selectedCollection = ref<string>('')
  const selectedItems = ref<number[]>([])
  const showModal = ref<boolean>(false)
  const selectedMedia: Ref<MediaItemType | null> = ref(null)
  const currentPage = ref<number>(1)
  const isDeleting = ref<boolean>(false)
  
  // Composable
  const {
    media,
    collections,
    loading,
    loadMedia,
    deleteMedia,
    searchMedia,
    applyFilters: applyGalleryFilters
  } = useMediaGallery({
    modelType: props.modelType,
    modelId: props.modelId,
    collection: props.collection,
    perPage: props.perPage
  })
  
  // Computed
  const displayedMedia = computed(() => media.value)
  
  const filteredMedia = computed(() => {
    let filtered = displayedMedia.value
  
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      filtered = filtered.filter(item => 
        item.name.toLowerCase().includes(query) ||
        item.file_name.toLowerCase().includes(query)
      )
    }
  
    if (selectedType.value) {
      filtered = filtered.filter(item => item.type === selectedType.value)
    }
  
    if (selectedCollection.value) {
      filtered = filtered.filter(item => item.collection_name === selectedCollection.value)
    }
  
    return filtered
  })
  
  const totalPages = computed(() => Math.ceil(filteredMedia.value.length / props.perPage))
  
  const paginatedMedia = computed(() => {
    const start = (currentPage.value - 1) * props.perPage
    const end = start + props.perPage
    return filteredMedia.value.slice(start, end)
  })
  
  const galleryClasses = computed(() => {
    const base = 'gallery-content'
    return [
      base,
      viewMode.value === MediaViewMode.GRID ? 'gallery-grid' : 'gallery-list'
    ]
  })
  
  const hasFilters = computed(() => {
    return searchQuery.value || selectedType.value || selectedCollection.value
  })
  
  const visiblePages = computed(() => {
    const pages: number[] = []
    const maxVisible = 5
    let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
    let end = Math.min(totalPages.value, start + maxVisible - 1)
    
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1)
    }
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    return pages
  })
  
  // Methods
  const handleItemClick = (media: MediaItemType): void => {
    emit('itemClick', media)
    if (props.showActions) {
      handlePreview(media)
    }
  }
  
  const handlePreview = (media: MediaItemType): void => {
    selectedMedia.value = media
    showModal.value = true
    emit('preview', media)
  }
  
  const handleEdit = (media: MediaItemType): void => {
    emit('edit', media)
  }
  
  const handleDelete = async (mediaId: number): Promise<void> => {
    try {
      await deleteMedia(mediaId)
      
      // Remove from selection if selected
      const index = selectedItems.value.indexOf(mediaId)
      if (index > -1) {
        selectedItems.value.splice(index, 1)
        emitSelectionChange()
      }
      
      emit('delete', mediaId)
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
    }
  }
  
  const toggleSelection = (mediaId: number): void => {
    const index = selectedItems.value.indexOf(mediaId)
    if (index > -1) {
      selectedItems.value.splice(index, 1)
    } else {
      selectedItems.value.push(mediaId)
    }
    emitSelectionChange()
  }
  
  const selectAll = (): void => {
    selectedItems.value = filteredMedia.value.map(item => item.id)
    emitSelectionChange()
  }
  
  const clearSelection = (): void => {
    selectedItems.value = []
    emitSelectionChange()
  }
  
  const deleteSelected = async (): Promise<void> => {
    if (!selectedItems.value.length) return
    
    const count = selectedItems.value.length
    if (!confirm(`Êtes-vous sûr de vouloir supprimer ${count} élément(s) ?`)) {
      return
    }
    
    isDeleting.value = true
    
    try {
      await Promise.all(selectedItems.value.map(id => deleteMedia(id)))
      selectedItems.value = []
      emitSelectionChange()
    } catch (error) {
      console.error('Erreur lors de la suppression multiple:', error)
    } finally {
      isDeleting.value = false
    }
  }
  
  const closeModal = (): void => {
    showModal.value = false
    selectedMedia.value = null
  }
  
  const goToPage = (page: number): void => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
    }
  }
  
  const applyFilters = (): void => {
    currentPage.value = 1
    applyGalleryFilters({
      search: searchQuery.value,
      type: selectedType.value,
      collection: selectedCollection.value
    })
  }
  
  const clearFilters = (): void => {
    searchQuery.value = ''
    selectedType.value = ''
    selectedCollection.value = ''
    currentPage.value = 1
    applyFilters()
  }
  
  const getEmptyMessage = (): string => {
    if (searchQuery.value) {
      return `Aucun résultat pour "${searchQuery.value}"`
    }
    if (selectedType.value || selectedCollection.value) {
      return 'Aucun média ne correspond aux filtres sélectionnés'
    }
    return 'Aucun fichier dans cette collection'
  }
  
  const emitSelectionChange = (): void => {
    const selectedMediaItems = media.value.filter(item => selectedItems.value.includes(item.id))
    emit('selectionChange', selectedItems.value, selectedMediaItems)
  }
  
  // Debounced search
  const debounceSearch = debounce(() => {
    applyFilters()
  }, 300)
  
  // Watchers
  watch([selectedType, selectedCollection], () => {
    currentPage.value = 1
  })
  
  watch(() => props.collection, () => {
    if (props.autoLoad) {
      loadMedia({
        modelType: props.modelType,
        modelId: props.modelId,
        collection: props.collection
      })
    }
  })
  
  // Lifecycle
  onMounted(async () => {
    if (props.autoLoad) {
      await loadMedia({
        modelType: props.modelType,
        modelId: props.modelId,
        collection: props.collection
      })
    }
  })
  
  // Expose methods
  defineExpose({
    loadMedia,
    refresh: () => loadMedia({
      modelType: props.modelType,
      modelId: props.modelId,
      collection: props.collection
    }),
    clearSelection,
    selectAll,
    goToPage,
    applyFilters,
    clearFilters
  })
  </script>
  
  <style scoped>
  .media-gallery {
    @apply w-full;
  }
  
  .gallery-header {
    @apply flex justify-between items-start mb-6 flex-wrap gap-4;
  }
  
  .gallery-title h2 {
    @apply text-xl font-semibold text-gray-900;
  }
  
  .gallery-filters {
    @apply flex items-center space-x-4 flex-wrap gap-2;
  }
  
  .filter-group {
    @apply flex flex-col;
  }
  
  .search-input {
    @apply px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64;
  }
  
  .filter-select {
    @apply px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
  }
  
  .view-controls {
    @apply flex border border-gray-300 rounded-md overflow-hidden;
  }
  
  .view-button {
    @apply px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-r border-gray-300 last:border-r-0 transition-colors duration-200;
  }
  
  .view-button.active {
    @apply bg-blue-500 text-white;
  }
  
  .selection-actions {
    @apply flex justify-between items-center p-4 bg-blue-50 border border-blue-200 rounded-lg mb-6;
  }
  
  .selection-info {
    @apply text-sm font-medium text-blue-800;
  }
  
  .selection-buttons {
    @apply flex space-x-2;
  }
  
  .selection-button {
    @apply px-3 py-1 text-sm border border-blue-300 text-blue-700 rounded hover:bg-blue-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed;
  }
  
  .selection-button.danger {
    @apply border-red-300 text-red-700 hover:bg-red-100;
  }
  
  .loading-state {
    @apply flex flex-col items-center justify-center py-12;
  }
  
  .loading-spinner {
    @apply w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4;
  }
  
  .gallery-content {
    @apply mb-6;
  }
  
  .gallery-grid {
    @apply grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4;
  }
  
  .gallery-list {
    @apply space-y-2;
  }
  
  .empty-state {
    @apply flex flex-col items-center justify-center py-12 text-center;
  }
  
  .empty-icon {
    @apply mb-4;
  }
  
  .empty-title {
    @apply text-lg font-medium text-gray-900 mb-2;
  }
  
  .empty-subtitle {
    @apply text-gray-500 mb-4;
  }
  
  .clear-filters-button {
    @apply px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors duration-200;
  }
  
  .pagination {
    @apply flex justify-between items-center;
  }
  
  .pagination-button {
    @apply flex items-center space-x-1 px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
  }
  
  .pagination-pages {
    @apply flex space-x-1;
  }
  
  .pagination-page {
    @apply px-3 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50 transition-colors duration-200;
  }
  
  .pagination-page.active {
    @apply bg-blue-500 text-white border-blue-500;
  }
  
  .pagination-info {
    @apply text-sm text-gray-600;
  }
  
  /* Transitions */
  .slide-down-enter-active, .slide-down-leave-active {
    transition: all 0.3s ease;
  }
  
  .slide-down-enter-from, .slide-down-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }
  
  .media-list-enter-active, .media-list-leave-active {
    transition: all 0.3s ease;
  }
  
  .media-list-enter-from, .media-list-leave-to {
    opacity: 0;
    transform: scale(0.9);
  }
  
  .media-list-move {
    transition: transform 0.3s ease;
  }
  </style>