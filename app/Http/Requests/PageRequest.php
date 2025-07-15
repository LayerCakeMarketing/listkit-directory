<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\UniqueUrlSlug;

class PageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => 'required|string|max:200',
            'slug' => [
                'nullable',
                'string',
                'max:200',
                Rule::unique('pages', 'slug')->ignore($pageId),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                new UniqueUrlSlug('page', $pageId)
            ],
            'content' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:300',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
        ];
    }
}