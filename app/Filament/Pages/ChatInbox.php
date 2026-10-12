<?php

namespace App\Filament\Pages;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class ChatInbox extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.chat-inbox';

    protected static ?string $navigationLabel = 'Chat Anggota & Pengunjung';

    protected static ?string $title = 'Chat dengan Anggota & Pengunjung';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public ?int $conversationId = null;

    public string $reply = '';

    public static function canAccess(): bool
    {
        return Gate::allows('admin');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }
        $count = ChatMessage::where('is_admin', false)->whereNull('read_at')->count();

        return $count ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table->query(ChatConversation::query()->with('user')->withCount([
            'messages as unread_count' => fn ($query) => $query->where('is_admin', false)->whereNull('read_at'),
        ]))->heading('Daftar percakapan')->description('Klik Chat untuk membaca dan membalas pesan.')->columns([
            TextColumn::make('row_number')->label('NO')->rowIndex()->width('4rem')->alignCenter(),
            TextColumn::make('user.name')->label('Nama pengirim')->placeholder(fn (ChatConversation $record): string => 'Pengunjung #'.$record->id)->searchable()->weight('semibold'),
            TextColumn::make('sender_type')->label('Jenis')->state(fn (ChatConversation $record): string => $record->user_id ? 'Anggota' : 'Pengunjung')->badge(),
            TextColumn::make('unread_count')->label('Pesan baru')->badge()->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
            TextColumn::make('last_message_at')->label('Pesan terakhir')->dateTime('d M Y H:i')->sortable(),
        ])->recordActions([
            Action::make('chat')->label('Chat')->icon('heroicon-o-chat-bubble-left-right')->button()
                ->action(fn (ChatConversation $record) => $this->selectConversation($record->id)),
            Action::make('deleteHistory')->label('Hapus Riwayat')->icon('heroicon-o-trash')->color('danger')
                ->requiresConfirmation()->modalHeading('Hapus riwayat chat?')
                ->modalDescription('Seluruh pesan dalam percakapan ini akan dihapus dari admin dan pengguna. Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus riwayat')
                ->action(function (ChatConversation $record): void {
                    Gate::authorize('admin');
                    app(ChatService::class)->deleteHistory($record, auth()->user());
                    $this->closeConversation();
                }),
        ])->defaultSort('last_message_at', 'desc')->striped()->poll('1s')
            ->emptyStateHeading('Belum ada percakapan')
            ->emptyStateDescription('Pesan pengunjung dan anggota akan muncul di sini.');
    }

    public function closeConversation(): void
    {
        Gate::authorize('admin');
        $this->conversationId = null;
        $this->reply = '';
        $this->resetValidation();
    }

    public function selectConversation(int $id): void
    {
        Gate::authorize('admin');
        ChatConversation::findOrFail($id);
        $this->conversationId = $id;
        $this->reply = '';
        $this->resetValidation();
        $this->resetPage('messagesPage');
    }

    public function sendReply(ChatService $service): void
    {
        Gate::authorize('admin');
        $this->validate(['reply' => ['required', 'string', 'max:2000', 'regex:/\S/u'], 'conversationId' => 'required|integer|exists:chat_conversations,id']);
        $conversation = ChatConversation::findOrFail($this->conversationId);
        $service->send($conversation, auth()->user(), trim($this->reply), true);
        $this->reply = '';
        $this->resetPage('messagesPage');
    }

    protected function getViewData(): array
    {
        Gate::authorize('admin');
        $selected = $this->conversationId ? ChatConversation::with('user')->find($this->conversationId) : null;
        if ($this->conversationId && ! $selected) {
            $this->conversationId = null;
            $this->reply = '';
        }
        $messages = $selected?->messages()->orderByDesc('id')->paginate(50, ['*'], 'messagesPage');
        if ($selected && $messages) {
            $selected->messages()->where('is_admin', false)->whereNull('read_at')->whereIn('id', $messages->pluck('id'))->update(['read_at' => now()]);
        }

        return compact('selected', 'messages');
    }
}
