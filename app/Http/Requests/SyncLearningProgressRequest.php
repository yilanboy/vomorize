<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncLearningProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'local_progress' => ['required', 'array', 'min:1', 'max:700'],
            'local_progress.*.group_id' => ['required', 'integer', 'exists:groups,id'],
            'local_progress.*.level_id' => ['nullable', 'integer'],
            'local_progress.*.stage' => ['required', 'integer', 'min:0', 'max:6'],
            'local_progress.*.last_score' => ['required', 'integer', 'min:0', 'max:100'],
            'local_progress.*.last_reviewed_at' => ['required', 'date'],
            'local_progress.*.next_review_at' => ['nullable', 'date'],
        ];
    }
}
