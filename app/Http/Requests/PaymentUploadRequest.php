<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return ['payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('is_active', true)], 'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', 'note' => 'nullable|string|max:1000'];
    }
}
