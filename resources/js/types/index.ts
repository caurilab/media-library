// Types principaux pour CAURI Media Library
export interface MediaItem {
    id: number
    uuid: string
    name: string
    file_name: string
    collection_name: string
    mime_type: string
    size: number
    human_readable_size: string
    type: 'image' | 'video' | 'audio' | 'document' | 'pdf' | 'archive'
    extension: string
    url: string
    urls: {
      original: string
      thumb: string
      medium: string
      large?: string
    }
    custom_properties: Record<string, any>
    order_column?: number
    created_at: string
    updated_at: string
    model_type?: string
    model_id?: number
    disk?: string
    path?: string
    generated_conversions?: Record<string, boolean>
  }
  
  export interface MediaCollection {
    name: string
    media_count: number
    total_size: number
    human_readable_size: string
  }
  
  export interface MediaUploadOptions {
    modelType?: string
    modelId?: number | string
    collection?: string
    customProperties?: Record<string, any>
  }
  
  export interface MediaUploadResponse {
    success: boolean
    message?: string
    data?: {
      media: MediaItem[]
      count: number
    }
    error?: string
    errors?: string[]
  }
  
  export interface MediaApiResponse<T = any> {
    success: boolean
    data?: T
    message?: string
    error?: string
    errors?: Record<string, string[]>
  }
  
  export interface MediaPagination {
    current_page: number
    per_page: number
    total: number
    last_page: number
    has_more?: boolean
  }
  
  export interface MediaStats {
    total_files: number
    total_size: number
    total_size_human: string
    by_type: {
      images: number
      videos: number
      audio: number
      documents: number
    }
    by_collection: Record<string, number>
    recent_uploads: MediaItem[]
  }
  
  export interface MediaSearchParams {
    q: string
    modelType?: string
    modelId?: number | string
    collection?: string
    type?: 'image' | 'video' | 'audio' | 'document'
    page?: number
    perPage?: number
  }
  
  export interface MediaSearchResponse {
    query: string
    results: MediaItem[]
    pagination: MediaPagination
  }
  
  export interface MediaConversion {
    name: string
    generated: boolean
    url?: string
  }
  
  // Types pour les composants
  export interface MediaUploaderProps {
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
  
  export interface MediaGalleryProps {
    title?: string
    subtitle?: string
    modelType?: string
    modelId?: number | string
    collection?: string
    showActions?: boolean
    selectable?: boolean
    showTimestamp?: boolean
    perPage?: number
    autoLoad?: boolean
  }
  
  export interface MediaItemProps {
    media: MediaItem
    showActions?: boolean
    selectable?: boolean
    selected?: boolean
    showTimestamp?: boolean
  }
  
  export interface MediaModalProps {
    media: MediaItem | null
    show?: boolean
  }
  
  // Types pour les erreurs
  export interface MediaError {
    message: string
    field?: string
    code?: string
  }
  
  // Types pour les events
  export interface MediaUploadEvent {
    files: File[]
    options: MediaUploadOptions
  }
  
  export interface MediaDeleteEvent {
    mediaId: number
  }
  
  export interface MediaUpdateEvent {
    media: MediaItem
    changes: Partial<MediaItem>
  }
  
  export interface MediaSelectionEvent {
    selectedIds: number[]
    selectedItems: MediaItem[]
  }
  
  // Types pour la configuration
  export interface MediaConfig {
    apiBaseUrl: string
    maxFileSize: number
    allowedMimeTypes: string[]
    defaultCollection: string
    conversions: Record<string, any>
  }
  
  // Types pour les filtres
  export interface MediaFilters {
    search?: string
    type?: string
    collection?: string
    modelType?: string
    modelId?: number | string
  }
  
  // Enums
  export enum MediaType {
    IMAGE = 'image',
    VIDEO = 'video',
    AUDIO = 'audio',
    DOCUMENT = 'document',
    PDF = 'pdf',
    ARCHIVE = 'archive'
  }
  
  export enum MediaViewMode {
    GRID = 'grid',
    LIST = 'list'
  }
  
  export enum MediaUploadStatus {
    IDLE = 'idle',
    UPLOADING = 'uploading',
    SUCCESS = 'success',
    ERROR = 'error'
  }