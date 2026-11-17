<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesClinicInput;
use App\Support\ClinicTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    use NormalizesClinicInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeClinicFields([
            'patient_code',
            'patient_name',
            'patient_contact',
        ]);
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'patient_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('patients', 'patient_code')->ignore($patient),
            ],
            'patient_name' => [
                'required',
                'string',
                'max:150',
            ],
            'patient_contact' => [
                'required',
                'string',
                'max:11',
                'regex:'.ClinicTextNormalizer::PHILIPPINE_MOBILE_NUMBER_PATTERN,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_code.required' => 'Please enter the patient code.',
            'patient_code.unique' => 'This patient code is already used by another patient.',
            'patient_code.max' => 'The patient code cannot exceed 50 characters.',
            'patient_name.required' => 'Please enter the patient full name.',
            'patient_name.max' => 'The patient name cannot exceed 150 characters.',
            'patient_contact.required' => 'Please enter the patient contact number.',
            'patient_contact.max' => 'The contact number must contain exactly 11 digits.',
            'patient_contact.regex' => 'Enter an 11-digit Philippine mobile number starting with 09.',
        ];
    }
}
