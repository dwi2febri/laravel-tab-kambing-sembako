<?php

namespace Tests\Feature;

use App\Filament\Pages\ChatInbox;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_guest_history_persists_and_is_private(): void
    {
        $token = str_repeat('a', 64);
        $this->withCookie('sakina_chat', $token)->postJson(route('chat.store'), ['body' => 'Halo, ada paket sembako?', 'is_admin' => true, 'sender_id' => 99])->assertCreated()->assertJsonPath('message.is_admin', false);
        $this->withCookie('sakina_chat', $token)->getJson(route('chat.index'))->assertOk()->assertJsonPath('name', null)->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Halo, ada paket sembako?');
        $this->withCookie('sakina_chat', str_repeat('b', 64))->getJson(route('chat.index'))->assertOk()->assertJsonCount(0, 'messages');
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertNull(ChatMessage::firstOrFail()->sender_id);
    }

    public function test_member_history_follows_account_and_guest_history_is_merged(): void
    {
        $member = User::factory()->create(['name' => 'Andi']);
        $this->actingAs($member)->postJson(route('chat.store'), ['body' => 'Pesan akun'])->assertCreated();
        $this->app['auth']->forgetGuards();
        $token = str_repeat('c', 64);
        $this->withCookie('sakina_chat', $token)->postJson(route('chat.store'), ['body' => 'Pesan tamu'])->assertCreated();
        $this->actingAs($member)->withCookie('sakina_chat', $token)->getJson(route('chat.index'))->assertOk()->assertJsonPath('name', 'Andi')->assertJsonCount(2, 'messages');
        $this->actingAs($member)->withCookie('sakina_chat', str_repeat('d', 64))->getJson(route('chat.index'))->assertOk()->assertJsonCount(2, 'messages');
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->actingAs(User::factory()->create())->getJson(route('chat.index'))->assertJsonCount(0, 'messages');
        $this->app['auth']->forgetGuards();
        $this->withCookie('sakina_chat', $token)->getJson(route('chat.index'))->assertJsonCount(0, 'messages');
    }

    public function test_admin_can_read_and_reply_and_member_receives_reply(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($member)->postJson(route('chat.store'), ['body' => 'Bagaimana cara bayar?'])->assertCreated();
        $conversation = ChatConversation::firstOrFail();
        $this->actingAs($admin)->get('/admin/chat-inbox')->assertOk();
        Livewire::test(ChatInbox::class)->assertCanSeeTableRecords([$conversation])
            ->callTableAction('chat', $conversation)->assertSet('conversationId', $conversation->id)
            ->set('reply', 'Silakan unggah bukti transfer.')->call('sendReply')->assertHasNoErrors()
            ->call('closeConversation')->assertSet('conversationId', null)->assertCanSeeTableRecords([$conversation]);
        $this->assertDatabaseHas('chat_messages', ['is_admin' => true, 'sender_id' => $admin->id, 'body' => 'Silakan unggah bukti transfer.']);
        $this->actingAs($member)->getJson(route('chat.index'))->assertOk()->assertJsonPath('messages.1.is_admin', true)->assertJsonPath('messages.1.body', 'Silakan unggah bukti transfer.');
        $this->assertNotNull(ChatMessage::where('is_admin', true)->firstOrFail()->read_at);
        $this->get('/admin/chat-inbox')->assertForbidden();
    }

    public function test_admin_can_delete_history_and_client_resets_then_starts_new_chat(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($member)->postJson(route('chat.store'), ['body' => 'Pesan lama'])->assertCreated();
        $conversation = ChatConversation::firstOrFail();
        $lastId = $conversation->messages()->max('id');
        $other = ChatConversation::create(['user_id' => User::factory()->create()->id]);
        app(ChatService::class)->send($other, $other->user, 'Percakapan lain');

        $this->actingAs($admin);
        Livewire::test(ChatInbox::class)->callTableAction('deleteHistory', $conversation)->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing('chat_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('chat_messages', ['chat_conversation_id' => $conversation->id]);
        $this->assertDatabaseHas('chat_messages', ['chat_conversation_id' => $other->id]);
        $this->actingAs($member)->getJson(route('chat.index', ['after' => $lastId, 'conversation_id' => $conversation->id]))
            ->assertOk()->assertJsonPath('reset', true)->assertJsonCount(0, 'messages');
        $this->postJson(route('chat.store'), ['body' => 'Percakapan baru'])->assertCreated();
        $this->getJson(route('chat.index', ['after' => $lastId, 'conversation_id' => $conversation->id]))
            ->assertOk()->assertJsonPath('reset', true)->assertJsonPath('messages.0.body', 'Percakapan baru');
    }

    public function test_member_cannot_delete_chat_history(): void
    {
        $member = User::factory()->create();
        $conversation = ChatConversation::create(['user_id' => $member->id]);
        $this->expectException(AuthorizationException::class);
        app(ChatService::class)->deleteHistory($conversation, $member);
    }

    public function test_chat_validation_and_inactive_account_protection(): void
    {
        foreach (['', '   ', str_repeat('x', 2001)] as $body) {
            $this->postJson(route('chat.store'), ['body' => $body])->assertUnprocessable()->assertJsonValidationErrors('body');
        }
        $this->assertDatabaseCount('chat_messages', 0);
        $this->actingAs(User::factory()->create(['is_active' => false]))->getJson(route('chat.index'))->assertForbidden();
        $this->postJson(route('chat.store'), ['body' => 'Halo'])->assertForbidden();
    }

    public function test_older_history_and_incremental_updates_are_accessible(): void
    {
        $member = User::factory()->create();
        $service = app(ChatService::class);
        $conversation = $service->resolve(str_repeat('e', 64), $member, true);
        for ($i = 0; $i < 55; $i++) {
            $service->send($conversation, $member, 'Pesan '.$i);
        }
        $response = $this->actingAs($member)->getJson(route('chat.index'))->assertOk()->assertJsonCount(50, 'messages')->assertJsonPath('has_more', true);
        $firstId = $response->json('messages.0.id');
        $lastId = $response->json('messages.49.id');
        $this->getJson(route('chat.index', ['before' => $firstId]))->assertJsonCount(5, 'messages')->assertJsonPath('has_more', false);
        $service->send($conversation, $member, 'Pesan terbaru');
        $this->getJson(route('chat.index', ['after' => $lastId]))->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Pesan terbaru');
    }
}
