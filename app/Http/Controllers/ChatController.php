<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(Request $request, ChatService $service): JsonResponse
    {
        $data = $request->validate(['before' => 'nullable|integer|min:1', 'after' => 'nullable|integer|min:0', 'conversation_id' => 'nullable|integer|min:0']);
        $token = $this->token($request);
        $conversation = $service->resolve($token, $request->user());
        $reset = isset($data['conversation_id']) && (int) $data['conversation_id'] !== (int) $conversation?->id;
        if ($reset) {
            unset($data['after'], $data['before']);
        }
        $messages = collect();
        $hasMore = false;
        if ($conversation) {
            $query = $conversation->messages();
            if (isset($data['after'])) {
                $messages = $query->where('id', '>', $data['after'])->orderBy('id')->limit(50)->get();
            } else {
                $messages = $query->when(isset($data['before']), fn ($q) => $q->where('id', '<', $data['before']))->orderByDesc('id')->limit(51)->get();
                $hasMore = $messages->count() > 50;
                $messages = $messages->take(50)->reverse()->values();
            }
            if ($messages->isNotEmpty()) {
                $conversation->messages()->where('is_admin', true)->whereNull('read_at')->whereIn('id', $messages->pluck('id'))->update(['read_at' => now()]);
            }
        }

        return response()->json([
            'conversation_id' => $conversation?->id,
            'reset' => $reset,
            'name' => $request->user()?->name,
            'messages' => $messages->map(fn (ChatMessage $message) => $this->messageData($message)),
            'has_more' => $hasMore,
        ])->cookie('sakina_chat', $token, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax')->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, ChatService $service): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000', 'regex:/\S/u']]);
        $token = $this->token($request);
        $conversation = $service->resolve($token, $request->user(), true);
        $message = $service->send($conversation, $request->user(), trim($data['body']));

        return response()->json(['message' => $this->messageData($message)], 201)
            ->cookie('sakina_chat', $token, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax')->header('Cache-Control', 'no-store');
    }

    private function token(Request $request): string
    {
        $token = $request->cookie('sakina_chat');

        return is_string($token) && preg_match('/^[A-Za-z0-9]{64}$/', $token) ? $token : Str::random(64);
    }

    private function messageData(ChatMessage $message): array
    {
        return ['id' => $message->id, 'body' => $message->body, 'is_admin' => $message->is_admin, 'created_at' => $message->created_at->toIso8601String()];
    }
}
