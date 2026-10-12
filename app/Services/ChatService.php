<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChatService
{
    public function resolve(string $token, ?User $user, bool $create = false): ?ChatConversation
    {
        return DB::transaction(function () use ($token, $user, $create): ?ChatConversation {
            if ($user) {
                abort_unless($user->is_active, 403);
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            }
            $guest = ChatConversation::whereNull('user_id')->where('guest_token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $user) {
                return $guest ?? ($create ? ChatConversation::firstOrCreate(['guest_token_hash' => hash('sha256', $token)]) : null);
            }
            $conversation = ChatConversation::where('user_id', $user->id)->first();
            if ($guest && $conversation) {
                $guest->messages()->update(['chat_conversation_id' => $conversation->id]);
                $conversation->update(['last_message_at' => $conversation->messages()->max('created_at')]);
                $guest->delete();
            } elseif ($guest) {
                $guest->update(['user_id' => $user->id, 'guest_token_hash' => null]);
                $conversation = $guest;
            }

            return $conversation ?? ($create ? ChatConversation::create(['user_id' => $user->id]) : null);
        });
    }

    public function deleteHistory(ChatConversation $conversation, User $admin): void
    {
        Gate::forUser($admin)->authorize('admin');
        DB::transaction(function () use ($conversation, $admin): void {
            $record = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $record->delete();
            SavingService::log($admin, 'chat.deleted', 'Riwayat percakapan #'.$conversation->id.' dihapus.');
        });
    }

    public function send(ChatConversation $conversation, ?User $sender, string $body, bool $asAdmin = false): ChatMessage
    {
        if ($asAdmin) {
            Gate::forUser($sender)->authorize('admin');
        }

        return DB::transaction(function () use ($conversation, $sender, $body, $asAdmin): ChatMessage {
            ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $message = $conversation->messages()->create(['sender_id' => $sender?->id, 'is_admin' => $asAdmin, 'body' => $body]);
            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });
    }
}
