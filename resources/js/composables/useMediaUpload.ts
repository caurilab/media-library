import { ref, computed, type Ref } from 'vue'
import type { 
  MediaItem, 
  MediaUploadOptions, 
  MediaUploadResponse,
  MediaUploadStatus,
  MediaError 
} from '../types'

interface UseMediaUploadReturn {
  // State
  progress: Ref<number>
  uploading: Ref<boolean>
  status: Ref<MediaUploadStatus>
  errors: Ref<string[]>
  uploadedFiles: Ref<MediaItem[]>
  
  // Computed
  hasErrors: Readonly<Ref<boolean>>
  isComplete: Readonly<Ref<boolean>>
  isIdle: Readonly<Ref<boolean>>
  
  // Methods
  uploadFiles: (files: File[], options?: MediaUploadOptions) => Promise<MediaUploadResponse>
  uploadFromUrl: (url: string, options?: MediaUploadOptions) => Promise<MediaUploadResponse>
  deleteFile: (mediaId: number) => Promise<boolean>
  clearErrors: () => void
  reset: () => void
}

export function useMediaUpload(): UseMediaUploadReturn {
  // State
  const progress = ref<number>(0)
  const uploading = ref<boolean>(false)
  const status = ref<MediaUploadStatus>(MediaUploadStatus.IDLE)
  const errors = ref<string[]>([])
  const uploadedFiles = ref<MediaItem[]>([])

  // Computed
  const hasErrors = computed(() => errors.value.length > 0)
  const isComplete = computed(() => status.value === MediaUploadStatus.SUCCESS)
  const isIdle = computed(() => status.value === MediaUploadStatus.IDLE)

  // Methods
  const uploadFiles = async (
    files: File[], 
    options: MediaUploadOptions = {}
  ): Promise<MediaUploadResponse> => {
    if (!files || files.length === 0) {
      throw new Error('Aucun fichier sélectionné')
    }

    uploading.value = true
    status.value = MediaUploadStatus.UPLOADING
    errors.value = []
    progress.value = 0

    const formData = new FormData()

    // Ajouter les fichiers
    Array.from(files).forEach((file, index) => {
      formData.append(`files[${index}]`, file)
    })

    // Ajouter les options
    Object.entries(options).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        formData.append(key, String(value))
      }
    })

    try {
      const response = await fetch('/api/cauri-media/upload', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: formData,
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaUploadResponse = await response.json()

      if (result.success && result.data?.media) {
        uploadedFiles.value.push(...result.data.media)
        status.value = MediaUploadStatus.SUCCESS
        return result
      } else {
        throw new Error(result.error || 'Upload failed')
      }

    } catch (error: any) {
      console.error('Upload error:', error)
      status.value = MediaUploadStatus.ERROR
      
      if (error.response?.data?.errors) {
        errors.value = Object.values(error.response.data.errors).flat() as string[]
      } else if (error.response?.data?.error) {
        errors.value = [error.response.data.error]
      } else if (error.response?.data?.message) {
        errors.value = [error.response.data.message]
      } else {
        errors.value = [error.message || 'Erreur lors de l\'upload des fichiers']
      }
      
      throw error
    } finally {
      uploading.value = false
    }
  }

  const uploadFromUrl = async (
    url: string, 
    options: MediaUploadOptions = {}
  ): Promise<MediaUploadResponse> => {
    uploading.value = true
    status.value = MediaUploadStatus.UPLOADING
    errors.value = []

    try {
      const response = await fetch('/api/cauri-media/upload-url', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify({
          url,
          ...options
        })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result: MediaUploadResponse = await response.json()

      if (result.success && result.data) {
        if (Array.isArray(result.data)) {
          uploadedFiles.value.push(...result.data)
        } else if (result.data) {
          uploadedFiles.value.push(result.data as MediaItem)
        }
        status.value = MediaUploadStatus.SUCCESS
        return result
      } else {
        throw new Error(result.error || 'Upload from URL failed')
      }

    } catch (error: any) {
      console.error('URL upload error:', error)
      status.value = MediaUploadStatus.ERROR
      
      if (error.response?.data?.error) {
        errors.value = [error.response.data.error]
      } else {
        errors.value = [error.message || 'Erreur lors de l\'upload depuis l\'URL']
      }
      
      throw error
    } finally {
      uploading.value = false
    }
  }

  const deleteFile = async (mediaId: number): Promise<boolean> => {
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
        // Retirer de la liste des fichiers uploadés
        uploadedFiles.value = uploadedFiles.value.filter(file => file.id !== mediaId)
        return true
      }
      
      throw new Error(result.error || 'Delete failed')
    } catch (error: any) {
      console.error('Delete error:', error)
      throw error
    }
  }

  const clearErrors = (): void => {
    errors.value = []
  }

  const reset = (): void => {
    progress.value = 0
    uploading.value = false
    status.value = MediaUploadStatus.IDLE
    errors.value = []
    uploadedFiles.value = []
  }

  return {
    // State
    progress,
    uploading,
    status,
    errors,
    uploadedFiles,
    
    // Computed
    hasErrors,
    isComplete,
    isIdle,
    
    // Methods
    uploadFiles,
    uploadFromUrl,
    deleteFile,
    clearErrors,
    reset,
  }
}

// Helper function pour obtenir le token CSRF
function getCSRFToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
  return meta?.content || ''
}