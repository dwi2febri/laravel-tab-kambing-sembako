<?php

namespace App\Livewire;

use App\Filament\Pages\ChatInbox;
use App\Filament\Resources\Handovers\HandoverResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\ChatMessage;
use App\Models\Handover;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdminNotificationBell extends Component
{
    use WithPagination;

    public ?string $expandedMenu = null;

    public function toggleMenu(string $menu): void
    {
        Gate::authorize('admin');
        abort_unless(in_array($menu, ['payment', 'handover', 'chat'], true), 404);
        $this->expandedMenu = $this->expandedMenu === $menu ? null : $menu;
    }

    protected function queueDetails(): array
    {
        return match ($this->expandedMenu) {
            'payment' => Payment::with(['user', 'userSaving'])->where('status', 'pending')->oldest()->limit(10)->get()->map(fn ($payment): array => [
                'id' => $payment->id,
                'title' => $payment->user->name,
                'message' => $payment->userSaving->product_name_snapshot.' - Rp '.number_format($payment->amount, 0, ',', '.').' menunggu konfirmasi',
                'url' => PaymentResource::getUrl('detail', ['record' => $payment->user_id]),
            ])->all(),
            'handover' => Handover::with('userSaving.user')->whereIn('status', ['requested', 'scheduled'])->oldest()->limit(10)->get()->map(fn ($handover): array => [
                'id' => $handover->id,
                'title' => $handover->userSaving->user->name,
                'message' => $handover->userSaving->product_name_snapshot.' - '.($handover->status === 'requested' ? 'Menunggu jadwal' : 'Menunggu penyerahan').'. Penerima: '.$handover->recipient_name,
                'url' => HandoverResource::getUrl(),
            ])->all(),
            'chat' => ChatMessage::with('conversation.user')->where('is_admin', false)->whereNull('read_at')->oldest()->limit(10)->get()->map(fn ($message): array => [
                'id' => $message->id,
                'title' => $message->conversation->user?->name ?? 'Pengunjung #'.$message->chat_conversation_id,
                'message' => Str::limit($message->body, 180),
                'url' => ChatInbox::getUrl(),
            ])->all(),
            default => [],
        };
    }

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

        $menus = [
            ['key' => 'payment', 'label' => 'Konfirmasi Pembayaran', 'count' => (int) PaymentResource::getNavigationBadge(), 'url' => PaymentResource::getUrl()],
            ['key' => 'handover', 'label' => 'Serah Terima', 'count' => (int) HandoverResource::getNavigationBadge(), 'url' => HandoverResource::getUrl()],
            ['key' => 'chat', 'label' => 'Chat Anggota & Pengunjung', 'count' => (int) ChatInbox::getNavigationBadge(), 'url' => ChatInbox::getUrl()],
        ];

        return view('livewire.admin-notification-bell', [
            'menus' => $menus,
            'queueDetails' => $this->queueDetails(),
            'actionCount' => array_sum(array_column($menus, 'count')),
            'notifications' => auth()->user()->memberNotifications()->latest('id')->paginate(10, pageName: 'notificationPage'),
            'unreadCount' => auth()->user()->memberNotifications()->whereNull('read_at')->count(),
        ]);
    }
}
