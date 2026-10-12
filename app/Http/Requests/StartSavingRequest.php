<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSavingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return ['saving_plan_id' => 'required|integer|exists:saving_plans,id'];
    }
}
