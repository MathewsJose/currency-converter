<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConvertCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'from_currency' => ['required', 'string', 'size:3', 'alpha'],
            'to_currency' => ['required', 'string', 'size:3', 'alpha'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be at least 0.01',
            'from_currency.required' => 'From currency is required',
            'from_currency.size' => 'From currency must be 3 characters',
            'from_currency.alpha' => 'From currency must contain only letters',
            'to_currency.required' => 'To currency is required',
            'to_currency.size' => 'To currency must be 3 characters',
            'to_currency.alpha' => 'To currency must contain only letters',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}