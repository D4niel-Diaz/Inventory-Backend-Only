<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BorrowTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|exists:items,id',
            'borrow_date' => 'required|date|after_or_equal:today',
            'due_date' => 'required|date|after:borrow_date',
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'The item selection is required.',
            'item_id.exists' => 'The selected item does not exist.',
            'borrow_date.required' => 'The borrow date is required.',
            'borrow_date.date' => 'The borrow date must be a valid date.',
            'borrow_date.after_or_equal' => 'The borrow date must be today or a future date.',
            'due_date.required' => 'The due date is required.',
            'due_date.date' => 'The due date must be a valid date.',
            'due_date.after' => 'The due date must be after the borrow date.',
        ];
    }

    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
