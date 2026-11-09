<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isGuest = ! ($this->user() instanceof User);

        return [
            'question_count' => ['required', 'integer', 'in:10,20,30,50,100'],
            'quiz_mode' => ['sometimes', 'string', 'in:standard'],
            'passed_group_ids' => [$isGuest ? 'required' : 'sometimes', 'array'],
            'passed_group_ids.*' => ['integer', 'exists:groups,id'],
        ];
    }
}
