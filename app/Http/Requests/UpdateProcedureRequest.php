<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesClinicInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcedureRequest extends FormRequest
{
    use NormalizesClinicInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeClinicFields(['procedure_name'], ['procedure_name']);
    }

    public function rules(): array
    {
        $procedure = $this->route('procedure');

        return [
            'procedure_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('procedures', 'procedure_name')->ignore($procedure),
            ],
            'procedure_cost' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'procedure_name.required' => 'Please enter the procedure name.',
            'procedure_name.unique' => 'This procedure name is already in use.',
            'procedure_name.max' => 'The procedure name cannot exceed 150 characters.',
            'procedure_cost.required' => 'Please enter the procedure cost.',
            'procedure_cost.numeric' => 'The procedure cost must be a number.',
            'procedure_cost.min' => 'The procedure cost cannot be negative.',
            'procedure_cost.max' => 'The procedure cost cannot exceed 99,999,999.99.',
            'procedure_cost.decimal' => 'The procedure cost can have at most two decimal places.',
        ];
    }
}
