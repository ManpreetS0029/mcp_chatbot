<?php

namespace App\Http\Requests;

use App\Models\WorkingDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slot_type' => ['required', Rule::in(['recurring', 'specific_date'])],
            'day_of_week' => [
                'nullable',
                Rule::requiredIf($this->input('slot_type') === 'recurring'),
                Rule::in(WorkingDay::DAYS_OF_WEEK),
            ],
            'specific_date' => [
                'nullable',
                Rule::requiredIf($this->input('slot_type') === 'specific_date'),
                'date_format:Y-m-d',
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
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
            'day_of_week.required' => 'Please select a day of the week for recurring slots.',
            'specific_date.required' => 'Please provide a date for specific date slots.',
            'end_time.after' => 'End time must be after the start time.',
            'capacity.min' => 'Capacity must be at least 1.',
        ];
    }
}
