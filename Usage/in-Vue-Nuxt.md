```vue
<template>
    <div>
      <!-- Upload simple -->
      <MediaUploader 
        :model-type="'App\\Models\\Product'"
        :model-id="productId"
        collection="gallery"
        @uploaded="handleUploaded"
      />
      
      <!-- Galerie complète -->
      <MediaGallery 
        :model-type="'App\\Models\\Product'"
        :model-id="productId"
        collection="gallery"
        :selectable="true"
        @selection-change="handleSelectionChange"
      />
    </div>
  </template>
  
  <script setup>
  import MediaUploader from '@/components/cauri-media/MediaUploader.vue'
  import MediaGallery from '@/components/cauri-media/MediaGallery.vue'
  
  const productId = ref(1)
  
  const handleUploaded = (result) => {
    console.log('Fichiers uploadés:', result)
  }
  
  const handleSelectionChange = (selectedIds) => {
    console.log('Sélection:', selectedIds)
  }
  </script>


/////////////////

  <script setup lang="ts">
import type { MediaItem } from '~/components/cauri-media/types'
import { useMediaUpload, useMediaGallery } from '~/components/cauri-media'

const { uploadFiles, progress, uploading } = useMediaUpload()
const { media, loadMedia } = useMediaGallery()

const handleUploaded = (result: any) => {
  console.log('Upload terminé:', result)
}
</script>
```

//////////  NUXT

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  css: ['~/assets/css/cauri-media.css'],
  plugins: ['~/plugins/cauri-media.client.ts']
})

// plugins/cauri-media.client.ts
import { CauriMediaPlugin } from '~/components/cauri-media'
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(CauriMediaPlugin)
})
```

```vue

<template>
  <div>
    <MediaUploader 
      :model-type="'App\\Models\\Product'"
      :model-id="productId"
      collection="gallery"
      @uploaded="handleUploaded"
    />
    
    <MediaGallery 
      :model-type="'App\\Models\\Product'"
      :model-id="productId"
      collection="gallery"
      :selectable="true"
    />
  </div>
</template>

<script setup lang="ts">
import type { MediaUploadResponse } from '~/types/cauri-media'

const productId = ref(1)

const handleUploaded = (result: MediaUploadResponse) => {
  console.log('Uploaded:', result.data?.media)
}
</script>