<?php

namespace Tests\Feature\Messaging;

use App\Models\Messaging\Message;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_messages_endpoint_returns_only_messages_after_the_known_id_and_marks_them_delivered(): void
    {
        $admin = $this->createMessagingUser('admin', 'admin@example.com');
        $manager = $this->createMessagingUser('manager', 'manager@example.com');

        $olderMessage = Message::query()->create([
            'sender_id' => $manager->id,
            'receiver_id' => $admin->id,
            'sender_role' => 'manager',
            'receiver_role' => 'admin',
            'content' => 'older',
            'status' => 'read',
            'read_at' => now(),
        ]);

        $freshMessage = Message::query()->create([
            'sender_id' => $manager->id,
            'receiver_id' => $admin->id,
            'sender_role' => 'manager',
            'receiver_role' => 'admin',
            'content' => 'fresh',
            'status' => 'sent',
        ]);

        $response = $this
            ->withToken(JWTAuth::fromUser($admin))
            ->getJson("/api/messaging/new-messages/{$manager->id}?after_id={$olderMessage->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $freshMessage->id)
            ->assertJsonPath('messages.0.content', 'fresh')
            ->assertJsonPath('messages.0.status', 'delivered');

        $this->assertDatabaseHas('messages', [
            'id' => $freshMessage->id,
            'status' => 'delivered',
        ]);
    }

    public function test_mark_conversation_delivered_acknowledges_pending_messages(): void
    {
        $admin = $this->createMessagingUser('admin', 'admin@example.com');
        $manager = $this->createMessagingUser('manager', 'manager@example.com');

        $pendingMessage = Message::query()->create([
            'sender_id' => $manager->id,
            'receiver_id' => $admin->id,
            'sender_role' => 'manager',
            'receiver_role' => 'admin',
            'content' => 'pending delivery',
            'status' => 'sent',
        ]);

        $this
            ->withToken(JWTAuth::fromUser($admin))
            ->postJson("/api/messaging/mark-conversation-delivered/{$manager->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('messages', [
            'id' => $pendingMessage->id,
            'status' => 'delivered',
        ]);
    }

    private function createMessagingUser(string $role, string $email): User
    {
        Role::findOrCreate($role, 'api');

        $user = User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('secret123'),
            'type' => $role,
            'avatar' => 'avatars/default.png',
            'lang' => 'en',
            'is_active' => true,
            'created_by' => 1,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
