<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\GenerateCustomQuizSession;
use App\Enums\QuizMode;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'question_count' => ['required', 'integer', Rule::in(GenerateCustomQuizSession::QUESTION_COUNT_OPTIONS)],
            'quiz_mode' => ['sometimes', 'string', Rule::in(QuizMode::availableValues())],
            'passed_group_ids' => [$isGuest ? 'required' : 'sometimes', 'array'],
            'passed_group_ids.*' => ['integer', 'exists:groups,id'],
        ];
    }
}
