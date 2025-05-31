<?php

namespace Cauri\MediaLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = $this->getMethod();

        if ($method === 'PATCH' || $method === 'PUT') {
            return $this->updateRules();
        }

        return [];
    }

    protected function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'collection_name' => 'sometimes|string|max:255',
            'custom_properties' => 'sometimes|array',
            'custom_properties.*' => 'nullable',
            'order_column' => 'sometimes|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name may not be longer than 255 characters.',
            'collection_name.string' => 'Collection name must be a string.',
            'collection_name.max' => 'Collection name may not be longer than 255 characters.',
            'custom_properties.array' => 'Custom properties must be an array.',
            'order_column.integer' => 'Order must be an integer.',
            'order_column.min' => 'Order must be at least 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'collection_name' => 'collection name',
            'custom_properties' => 'custom properties',
            'order_column' => 'order',
        ];
    }
}