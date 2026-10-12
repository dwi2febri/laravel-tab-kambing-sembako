<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class AdminLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        session()->forget('url.intended');

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
