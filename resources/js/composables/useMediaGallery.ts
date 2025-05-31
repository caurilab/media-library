import { ref, reactive, computed, type Ref } from 'vue'
import type { 
  MediaItem, 
  MediaCollection,
  MediaApiResponse,
  MediaPagination,
  MediaStats,
  MediaSearchParams,
  MediaSearchResponse,
  MediaFilters
} from '../types'

interface UseMediaGalleryOptions {
  modelType?: string
  modelId?: number | string
  collection?: string
  perPage?: number
}

interface UseMediaGalleryReturn {
  // State
  media: Ref<MediaItem[]>
  collections: Ref<MediaCollection[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  pagination: MediaPagination
  
  // Computed
  isEmpty: Readonly<Ref<boolean>>
  totalSize: Readonly<Ref<number>>
  
  // Methods
  loadMedia: (options?: UseMediaGalleryOptions) => Promise<void>
  loadCollections: (options?: UseMediaGalleryOptions) => Promise<void>
  searchMedia: (params: MediaSearchParams) => Promise<MediaSearchResponse>
  deleteMedia: (mediaId: number) => Promise<boolean>
  updateMedia: (mediaId: number, data: Partial<MediaItem>) => Promise<MediaItem>
  reorderMedia: (modelType: string, modelId: number | string, collection: string, mediaIds: number[]) => Promise<boolean>
  getStats: (options?: UseMediaGalleryOptions) => Promise<MediaStats>
  refresh: () => Promise<void>
  applyFilters: (filters: MediaFilters) => void
}

export function useMediaGallery(initialOptions: UseMediaGalleryOptions = {}): UseMediaGalleryReturn {
  // State
  const media = ref<MediaItem[]>([])
  const collections = ref<MediaCollection[]>([])
  const loading = ref<boolean>(false)
  const error = ref<string | null>(null)
  const currentOptions = ref<UseMediaGalleryOptions>(initialOptions)
  
  const pagination = reactive<MediaPagination>({
    current_page: 1,
    per_page: 24,
    total: 0,
    last_page: 1
  })

  // Computed
  const isEmpty = computed(() => media.value.length === 0)
  const totalSize = computed(() => media.value.reduce((sum, item) => sum + item.size, 0))

  // Methods
  const loadMedia = async (options: UseMediaGalleryOptions = {}): Promise<void> => {
    loading.value = true
    error.value = null
    
    const mergedOptions = { ...currentOptions.value, ...options }
    currentOptions.value = mergedOptions

    try {
      const params = new URLSearchParams()
      
      if (mergedOptions.modelType) params.append('model_type', mergedOptions.modelType)
      if (mergedOptions.modelId) params.append('model_id', String(mergedOptions.modelId))
      if (mergedOptions.collection) params.append('collection', mergedOptions.collection)
      if (mergedOptions.perPage) params.append('per_page', String(mergedOptions.perPage))

      const endpoint = mergedOptions.collection 
        ? `/api/cauri-media/collection/${mergedOptions.collection}`
        : '/api/cauri-media/search'

      const response = await fetch(`${endpoint}?${params}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaApiResponse = await response.json()
      
      if (result.success && result.data) {
        media.value = result.data.media || result.data.results || []
        
        if (result.data.pagination) {
          Object.assign(pagination, result.data.pagination)
        }
      } else {
        throw new Error(result.error || 'Failed to load media')
      }

    } catch (err: any) {
      console.error('Error loading media:', err)
      error.value = err.message || 'Erreur lors du chargement'
    } finally {
      loading.value = false
    }
  }

  const loadCollections = async (options: UseMediaGalleryOptions = {}): Promise<void> => {
    try {
      const params = new URLSearchParams()
      
      if (options.modelType) params.append('model_type', options.modelType)
      if (options.modelId) params.append('model_id', String(options.modelId))

      const response = await fetch(`/api/cauri-media/stats?${params}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaApiResponse<MediaStats> = await response.json()
      
      if (result.success && result.data?.by_collection) {
        collections.value = Object.entries(result.data.by_collection).map(([name, count]) => ({
          name,
          media_count: count,
          total_size: 0,
          human_readable_size: '0 B'
        }))
      }

    } catch (err: any) {
      console.error('Error loading collections:', err)
    }
  }

  const searchMedia = async (params: MediaSearchParams): Promise<MediaSearchResponse> => {
    loading.value = true
    error.value = null

    try {
      const searchParams = new URLSearchParams()
      
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
          searchParams.append(key, String(value))
        }
      })

      const response = await fetch(`/api/cauri-media/search?${searchParams}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaApiResponse<MediaSearchResponse> = await response.json()
      
      if (result.success && result.data) {
        media.value = result.data.results || []
        
        if (result.data.pagination) {
          Object.assign(pagination, result.data.pagination)
        }

        return result.data
      } else {
        throw new Error(result.error || 'Search failed')
      }

    } catch (err: any) {
      console.error('Error searching media:', err)
      error.value = err.message || 'Erreur lors de la recherche'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteMedia = async (mediaId: number): Promise<boolean> => {
    try {
      const response = await fetch(`/api/cauri-media/${mediaId}`, {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result = await response.json()
      
      if (result.success) {
        // Retirer de la liste
        media.value = media.value.filter(item => item.id !== mediaId)
        return true
      }
      
      throw new Error(result.error || 'Delete failed')
    } catch (err: any) {
      console.error('Error deleting media:', err)
      throw err
    }
  }

  const updateMedia = async (mediaId: number, data: Partial<MediaItem>): Promise<MediaItem> => {
    try {
      const response = await fetch(`/api/cauri-media/${mediaId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(data)
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaApiResponse<MediaItem> = await response.json()
      
      if (result.success && result.data) {
        // Mettre à jour dans la liste
        const index = media.value.findIndex(item => item.id === mediaId)
        if (index > -1) {
          media.value[index] = { ...media.value[index], ...result.data }
        }
        
        return result.data
      }
      
      throw new Error(result.error || 'Update failed')
    } catch (err: any) {
      console.error('Error updating media:', err)
      throw err
    }
  }

  const reorderMedia = async (
    modelType: string, 
    modelId: number | string, 
    collection: string, 
    mediaIds: number[]
  ): Promise<boolean> => {
    try {
      const response = await fetch('/api/cauri-media/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify({
          model_type: modelType,
          model_id: modelId,
          collection,
          media_ids: mediaIds
        })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result = await response.json()
      return result.success
    } catch (err: any) {
      console.error('Error reordering media:', err)
      throw err
    }
  }

  const getStats = async (options: UseMediaGalleryOptions = {}): Promise<MediaStats> => {
    try {
      const params = new URLSearchParams()
      
      if (options.modelType) params.append('model_type', options.modelType)
      if (options.modelId) params.append('model_id', String(options.modelId))

      const response = await fetch(`/api/cauri-media/stats?${params}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaApiResponse<MediaStats> = await response.json()
      
      if (result.success && result.data) {
        return result.data
      }
      
      throw new Error(result.error || 'Failed to get stats')
    } catch (err: any) {
      console.error('Error getting stats:', err)
      throw err
    }
  }

  const refresh = async (): Promise<void> => {
    await loadMedia(currentOptions.value)
  }

  const applyFilters = (filters: MediaFilters): void => {
    const filteredMedia = media.value.filter(item => {
      if (filters.search && !item.name.toLowerCase().includes(filters.search.toLowerCase())) {
        return false
      }
      if (filters.type && item.type !== filters.type) {
        return false
      }
      if (filters.collection && item.collection_name !== filters.collection) {
        return false
      }
      return true
    })
    
    media.value = filteredMedia
  }

  return {
    // State
    media,
    collections,
    loading,
    error,
    pagination,
    
    // Computed
    isEmpty,
    totalSize,
    
    // Methods
    loadMedia,
    loadCollections,
    searchMedia,
    deleteMedia,
    updateMedia,
    reorderMedia,
    getStats,
    refresh,
    applyFilters,
  }
}

// Helper function pour obtenir le token CSRF
function getCSRFToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
  return meta?.content || ''
}