<?php

namespace App\Http\Requests;

use App\Models\UserSaving;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $saving = $this->route('saving');

        return $saving instanceof UserSaving && $this->user()?->role === 'user' && $this->user()->can('view', $saving);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['received' => ['required', 'accepted']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['received.accepted' => 'Pastikan produk sudah Anda terima sebelum menyelesaikan serah terima.'];
    }
}
