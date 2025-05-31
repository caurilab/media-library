<?php

namespace Cauri\MediaLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajuste selon tes besoins d'autorisation
    }

    public function rules(): array
    {
        $maxFileSize = config('cauri-media-library.max_file_size') / 1024; // Convert to KB
        $allowedMimeTypes = implode(',', config('cauri-media-library.allowed_mime_types', []));

        return [
            'files' => 'required|array|min:1|max:20',
            'files.*' => [
                'required',
                'file',
                "max:{$maxFileSize}",
                function ($attribute, $value, $fail) {
                    $mimeType = $value->getMimeType();
                    $allowedTypes = config('cauri-media-library.allowed_mime_types', []);
                    
                    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
            'collection' => 'sometimes|string|max:255',
            'custom_properties' => 'sometimes|array',
            'custom_properties.*' => 'nullable',
        ];
    }

    public function messages(): array
    {
        $maxSizeMB = config('cauri-media-library.max_file_size') / 1024 / 1024;
        
        return [
            'files.required' => 'At least one file is required.',
            'files.*.required' => 'Each file is required.',
            'files.*.file' => 'Each upload must be a valid file.',
            'files.*.max' => "Each file may not be larger than {$maxSizeMB}MB.",
            'model_type.string' => 'Model type must be a string.',
            'model_id.integer' => 'Model ID must be an integer.',
            'collection.string' => 'Collection name must be a string.',
            'collection.max' => 'Collection name may not be longer than 255 characters.',
            'custom_properties.array' => 'Custom properties must be an array.',
        ];
    }

    public function attributes(): array
    {
        return [
            'files' => 'files',
            'files.*' => 'file',
            'model_type' => 'model type',
            'model_id' => 'model ID',
            'collection' => 'collection',
            'custom_properties' => 'custom properties',
        ];
    }
}