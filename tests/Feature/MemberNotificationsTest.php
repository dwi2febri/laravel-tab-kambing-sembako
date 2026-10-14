<?php

namespace Tests\Feature;

use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_private_and_paginated_five_per_page(): void
    {
        $member = User::factory()->create();
        $notifications = MemberNotification::factory()->count(6)->create(['user_id' => $member->id]);
        $other = MemberNotification::factory()->create();
        $this->actingAs($member)->get(route('notifications'))->assertOk()
            ->assertViewHas('notifications', fn ($page): bool => $page->count() === 5 && $page->total() === 6 && $page->first()->id === $notifications->last()->id)
            ->assertSee('Sebelumnya')->assertSee('Selanjutnya')
            ->assertViewHas('notifications', fn ($page): bool => ! $page->contains('id', $other->id));
        $this->get(route('notifications', ['page' => 2]))->assertOk()
            ->assertViewHas('notifications', fn ($page): bool => $page->count() === 1 && $page->first()->id === $notifications->first()->id);
        $this->post(route('notifications.read', $notifications->last()))->assertRedirect();
        $this->assertNotNull($notifications->last()->fresh()->read_at);
        $this->post(route('notifications.read', $other))->assertForbidden();
    }
}
