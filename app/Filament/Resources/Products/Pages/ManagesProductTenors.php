<?php

namespace App\Filament\Resources\Products\Pages;

use Illuminate\Support\Str;

trait ManagesProductTenors
{
    public array $tenorDraft = ['months' => '', 'admin_fee' => '0', 'is_active' => true];

    public ?string $editingTenor = null;

    public function saveTenor(): void
    {
        $this->tenorDraft['admin_fee'] = str_replace('.', '', (string) ($this->tenorDraft['admin_fee'] ?? '0'));
        $this->validate([
            'tenorDraft.months' => ['required', 'integer', 'min:1', 'max:120'],
            'tenorDraft.admin_fee' => ['required', 'integer', 'min:0'],
            'tenorDraft.is_active' => ['boolean'],
        ]);
        $key = $this->editingTenor ?? (string) Str::uuid();
        $this->data['plans'][$key] = [
            ...($this->data['plans'][$key] ?? []),
            'name' => $this->tenorDraft['months'].' bulan',
            'months' => (int) $this->tenorDraft['months'],
            'admin_fee' => (int) $this->tenorDraft['admin_fee'],
            'down_payment' => 0,
            'monthly_amount' => null,
            'financed_admin_fee' => true,
            'is_active' => (bool) $this->tenorDraft['is_active'],
        ];
        $this->cancelTenor();
    }

    public function editTenor(string $key): void
    {
        abort_unless(isset($this->data['plans'][$key]), 404);
        $plan = $this->data['plans'][$key];
        $this->editingTenor = $key;
        $this->tenorDraft = ['months' => $plan['months'], 'admin_fee' => number_format((int) $plan['admin_fee'], 0, ',', '.'), 'is_active' => (bool) $plan['is_active']];
        $this->resetValidation();
    }

    public function cancelTenor(): void
    {
        $this->editingTenor = null;
        $this->tenorDraft = ['months' => '', 'admin_fee' => '0', 'is_active' => true];
        $this->resetValidation();
    }
}
