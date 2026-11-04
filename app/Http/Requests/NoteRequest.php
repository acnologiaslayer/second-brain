<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uid = $this->user()->id;
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:100000'],
            'note_type' => ['required', Rule::in(Note::TYPES)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $uid)],
            'tags' => ['nullable', 'string', 'max:500'],
            'is_favorite' => ['sometimes', 'boolean'],
            'is_pinned' => ['sometimes', 'boolean'],
        ];
    }
}
