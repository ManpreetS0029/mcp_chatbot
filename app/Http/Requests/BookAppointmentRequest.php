<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'appointment_date.after_or_equal' => 'Please select a date that is today or in the future.',
            'time_slot_id.required' => 'Please select an available time slot.',
            'time_slot_id.exists' => 'The selected time slot is invalid.',
        ];
    }
}
