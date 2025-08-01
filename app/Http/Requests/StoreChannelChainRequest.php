<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChannelChainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the user owns the channel they're trying to create a chain for
        $ownerId = $this->input('owner_id');
        $ownerType = $this->input('owner_type');
        
        // Handle both short and full class name formats
        if (($ownerType === 'channel' || $ownerType === 'App\Models\Channel') && $ownerId) {
            $channel = \App\Models\Channel::find($ownerId);
            return $channel && $channel->user_id === $this->user()->id;
        }
        
        // If creating for user, must be the authenticated user
        if ($ownerType === 'user' || $ownerType === 'App\Models\User' || !$ownerType) {
            return true; // Will default to user's own chains
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|string|url',
            'cover_cloudflare_id' => 'nullable|string|max:255',
            'visibility' => 'required|in:public,private,unlisted',
            'status' => 'required|in:draft,published',
            'lists' => 'required|array|min:2|max:50',
            'lists.*.list_id' => [
                'required',
                'integer',
                'exists:lists,id',
                function ($attribute, $value, $fail) {
                    // Check if user can add this list to a chain
                    $list = \App\Models\UserList::find($value);
                    if (!$list) {
                        $fail('The selected list does not exist.');
                        return;
                    }
                    
                    // Public lists can be added by anyone
                    if ($list->visibility === 'public') {
                        return;
                    }
                    
                    // Private lists can only be added by owner
                    if (!$list->canEdit($this->user())) {
                        $fail('You do not have permission to add this list to a chain.');
                    }
                },
            ],
            'lists.*.label' => 'nullable|string|max:100',
            'lists.*.description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'metadata.sections' => 'nullable|array',
            'metadata.sections.*.name' => 'nullable|string|max:100',
            'metadata.sections.*.listCount' => 'nullable|integer|min:0',
            'owner_type' => ['nullable', 'string', Rule::in(['user', 'channel', 'App\Models\User', 'App\Models\Channel'])],
            'owner_id' => 'nullable|integer',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a name for your chain.',
            'lists.required' => 'A chain must contain at least 2 lists.',
            'lists.min' => 'A chain must contain at least 2 lists.',
            'lists.max' => 'A chain cannot contain more than 50 lists.',
            'lists.*.list_id.required' => 'Each list item must have a valid list ID.',
            'lists.*.list_id.exists' => 'One or more selected lists do not exist.',
            'visibility.required' => 'Please select a visibility option.',
            'status.required' => 'Please select a status for your chain.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure owner_type uses full class names
        if ($this->has('owner_type')) {
            $ownerType = $this->input('owner_type');
            if ($ownerType === 'channel') {
                $this->merge(['owner_type' => 'App\Models\Channel']);
            } elseif ($ownerType === 'user') {
                $this->merge(['owner_type' => 'App\Models\User']);
            }
        }
    }
}