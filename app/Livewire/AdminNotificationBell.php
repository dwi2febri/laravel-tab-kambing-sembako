<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AdminNotificationBell extends Component
{
    use WithPagination;

    public function markRead(int $notificationId): void
    {
        Gate::authorize('admin');
        auth()->user()->memberNotifications()->findOrFail($notificationId)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Gate::authorize('admin');
        auth()->user()->memberNotifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function render(): View
    {
        Gate::authorize('admin');

        return view('livewire.admin-notification-bell', [
            'notifications' => auth()->user()->memberNotifications()->latest('id')->paginate(10, pageName: 'notificationPage'),
            'unreadCount' => auth()->user()->memberNotifications()->whereNull('read_at')->count(),
        ]);
    }
}
