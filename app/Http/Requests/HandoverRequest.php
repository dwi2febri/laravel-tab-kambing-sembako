<?php

namespace App\Http\Requests;

use App\Models\UserSaving;
use Illuminate\Foundation\Http\FormRequest;

class HandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $saving = $this->route('saving');

        return $saving instanceof UserSaving && $this->user()?->role === 'user' && $this->user()->can('view', $saving);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'preferred_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:now'],
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['preferred_at' => 'tanggal dan jam serah terima', 'location' => 'alamat lokasi', 'latitude' => 'titik lokasi', 'longitude' => 'titik lokasi'];
    }
}
