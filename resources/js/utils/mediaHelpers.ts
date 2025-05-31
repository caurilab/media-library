import type { MediaItem, MediaType } from '../types'

/**
 * Utilitaires pour la gestion des médias
 */

export const MIME_TYPE_ICONS: Record<string, string> = {
  'image/jpeg': '🖼️',
  'image/png': '🖼️',
  'image/gif': '🖼️',
  'image/webp': '🖼️',
  'video/mp4': '🎥',
  'video/webm': '🎥',
  'audio/mp3': '🎵',
  'audio/wav': '🎵',
  'application/pdf': '📄',
  'application/zip': '📦',
}

export const FILE_EXTENSIONS: Record<string, MediaType> = {
  jpg: 'image',
  jpeg: 'image',
  png: 'image',
  gif: 'image',
  webp: 'image',
  svg: 'image',
  mp4: 'video',
  webm: 'video',
  mov: 'video',
  mp3: 'audio',
  wav: 'audio',
  ogg: 'audio',
  pdf: 'document',
  doc: 'document',
  docx: 'document',
  zip: 'archive',
  rar: 'archive',
}

/**
 * Formate la taille d'un fichier en format lisible
 */
export function formatFileSize(bytes: number, decimals: number = 2): string {
  if (bytes === 0) return '0 B'

  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB']

  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}

/**
 * Obtient le type de média à partir du MIME type
 */
export function getMediaTypeFromMime(mimeType: string): MediaType {
  if (mimeType.startsWith('image/')) return 'image'
  if (mimeType.startsWith('video/')) return 'video'
  if (mimeType.startsWith('audio/')) return 'audio'
  if (mimeType === 'application/pdf') return 'pdf'
  if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('archive')) return 'archive'
  return 'document'
}

/**
 * Obtient l'icône appropriée pour un type de média
 */
export function getMediaIcon(mimeType: string): string {
  return MIME_TYPE_ICONS[mimeType] || '📁'
}

/**
 * Valide si un fichier est accepté selon les types autorisés
 */
export function isFileAccepted(file: File, acceptedTypes?: string): boolean {
  if (!acceptedTypes) return true

  const allowedTypes = acceptedTypes.split(',').map(type => type.trim())
  
  return allowedTypes.some(type => {
    if (type.endsWith('/*')) {
      return file.type.startsWith(type.slice(0, -1))
    }
    return file.type === type
  })
}

/**
 * Génère un nom de fichier unique
 */
export function generateUniqueFileName(originalName: string): string {
  const timestamp = Date.now()
  const random = Math.random().toString(36).substring(2, 8)
  const extension = originalName.split('.').pop()
  const nameWithoutExt = originalName.substring(0, originalName.lastIndexOf('.'))
  
  return `${nameWithoutExt}-${timestamp}-${random}.${extension}`
}

/**
 * Crée une URL de prévisualisation pour un fichier
 */
export function createPreviewUrl(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    if (!file.type.startsWith('image/')) {
      reject(new Error('Le fichier n\'est pas une image'))
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      resolve(e.target?.result as string)
    }
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

/**
 * Compresse une image
 */
export function compressImage(
  file: File, 
  maxWidth: number = 1920, 
  maxHeight: number = 1080, 
  quality: number = 0.8
): Promise<Blob> {
  return new Promise((resolve, reject) => {
    if (!file.type.startsWith('image/')) {
      reject(new Error('Le fichier n\'est pas une image'))
      return
    }

    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    const img = new Image()

    img.onload = () => {
      // Calculer les nouvelles dimensions
      let { width, height } = img
      
      if (width > height) {
        if (width > maxWidth) {
          height = (height * maxWidth) / width
          width = maxWidth
        }
      } else {
        if (height > maxHeight) {
          width = (width * maxHeight) / height
          height = maxHeight
        }
      }

      canvas.width = width
      canvas.height = height

      // Dessiner l'image redimensionnée
      ctx?.drawImage(img, 0, 0, width, height)

      // Convertir en blob
      canvas.toBlob(
        (blob) => {
          if (blob) {
            resolve(blob)
          } else {
            reject(new Error('Erreur lors de la compression'))
          }
        },
        file.type,
        quality
      )
    }

    img.onerror = reject
    img.src = URL.createObjectURL(file)
  })
}

/**
 * Valide la taille d'un fichier
 */
export function validateFileSize(file: File, maxSize: number): boolean {
  return file.size <= maxSize
}

/**
 * Obtient les métadonnées d'une image
 */
export function getImageMetadata(file: File): Promise<{width: number, height: number}> {
  return new Promise((resolve, reject) => {
    if (!file.type.startsWith('image/')) {
      reject(new Error('Le fichier n\'est pas une image'))
      return
    }

    const img = new Image()
    
    img.onload = () => {
      resolve({
        width: img.naturalWidth,
        height: img.naturalHeight
      })
      URL.revokeObjectURL(img.src)
    }
    
    img.onerror = reject
    img.src = URL.createObjectURL(file)
  })
}

/**
 * Filtre une liste de médias
 */
export function filterMedia(
  media: MediaItem[], 
  filters: {
    search?: string
    type?: string
    collection?: string
  }
): MediaItem[] {
  return media.filter(item => {
    if (filters.search) {
      const search = filters.search.toLowerCase()
      if (!item.name.toLowerCase().includes(search) && 
          !item.file_name.toLowerCase().includes(search)) {
        return false
      }
    }

    if (filters.type && item.type !== filters.type) {
      return false
    }

    if (filters.collection && item.collection_name !== filters.collection) {
      return false
    }

    return true
  })
}

/**
 * Trie une liste de médias
 */
export function sortMedia(
  media: MediaItem[], 
  sortBy: 'name' | 'size' | 'created_at' | 'type' = 'created_at',
  order: 'asc' | 'desc' = 'desc'
): MediaItem[] {
  return [...media].sort((a, b) => {
    let aValue: any = a[sortBy]
    let bValue: any = b[sortBy]

    if (sortBy === 'created_at') {
      aValue = new Date(aValue).getTime()
      bValue = new Date(bValue).getTime()
    }

    if (typeof aValue === 'string') {
      aValue = aValue.toLowerCase()
      bValue = bValue.toLowerCase()
    }

    if (order === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })
}

/**
 * Groupe les médias par collection
 */
export function groupMediaByCollection(media: MediaItem[]): Record<string, MediaItem[]> {
  return media.reduce((groups, item) => {
    const collection = item.collection_name || 'default'
    if (!groups[collection]) {
      groups[collection] = []
    }
    groups[collection].push(item)
    return groups
  }, {} as Record<string, MediaItem[]>)
}

/**
 * Calcule la taille totale d'une liste de médias
 */
export function getTotalSize(media: MediaItem[]): number {
  return media.reduce((total, item) => total + item.size, 0)
}

/**
 * Obtient les statistiques d'une liste de médias
 */
export function getMediaStats(media: MediaItem[]) {
  const stats = {
    total: media.length,
    totalSize: getTotalSize(media),
    byType: {} as Record<string, number>,
    byCollection: {} as Record<string, number>
  }

  media.forEach(item => {
    // Par type
    stats.byType[item.type] = (stats.byType[item.type] || 0) + 1
    
    // Par collection
    const collection = item.collection_name || 'default'
    stats.byCollection[collection] = (stats.byCollection[collection] || 0) + 1
  })

  return stats
}
