<?php

namespace App\Http\Requests;

use App\Models\WorkingDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreTimeSlotsRequest extends FormRequest
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
        return [
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['string', Rule::in(WorkingDay::DAYS_OF_WEEK)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'duration_minutes' => ['required', 'integer', Rule::in([15, 20, 30, 45, 60, 90, 120])],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.required' => 'Please select at least one day.',
            'end_time.after' => 'End time must be after the start time.',
            'duration_minutes.in' => 'Please select a valid slot duration.',
        ];
    }
}
