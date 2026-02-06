<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VpnClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use function PHPUnit\Framework\assertEquals;

class VpnClientTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_routes_require_authentication()
    {
        $client = VpnClient::factory()->create();
        $id = $client->id;

        $this->getJson('/api/vpn-clients')->assertStatus(401);
        $this->postJson('/api/vpn-clients', [])->assertStatus(401);
        $this->getJson("/api/vpn-clients/$id")->assertStatus(401);
        $this->putJson("/api/vpn-clients/$id", [])->assertStatus(401);
        $this->deleteJson("/api/vpn-clients/$id")->assertStatus(401);
    }

    public function test_index_returns_clients()
    {
        $user = $this->authUser();

        VpnClient::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $this->getJson('/api/vpn-clients')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_add_creates_client_and_returns_it()
    {
        $user = $this->authUser();

        $payload = [
            'user_id' => $user->id,
            'client_name' => 'client-001',
            'status' => 'pending',
            'notes' => 'hello',
        ];

        $res = $this->postJson('/api/vpn-clients', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('vpn_clients', [
            'user_id' => $user->id,
            'client_name' => 'client-001',
            'status' => 'pending',
            'notes' => 'hello',
        ]);

        $res->assertJsonPath('client_name', 'client-001');
    }

    public function test_add_validates_client_name_unique_and_length()
    {
        $user = $this->authUser();

        VpnClient::factory()->create([
            'user_id' => $user->id,
            'client_name' => 'dup-name',
        ]);

        // duplicate should fail
        $this->postJson('/api/vpn-clients', [
            'user_id' => $user->id,
            'client_name' => 'dup-name',
        ])->assertStatus(422);

        // too short should fail (between:3,50)
        $this->postJson('/api/vpn-clients', [
            'user_id' => $user->id,
            'client_name' => 'aa',
        ])->assertStatus(422);

        // too long should fail
        $this->postJson('/api/vpn-clients', [
            'user_id' => $user->id,
            'client_name' => str_repeat('a', 51),
        ])->assertStatus(422);
    }

    public function test_show_returns_single_client()
    {
        $user = $this->authUser();

        $client = VpnClient::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->getJson("/api/vpn-clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('id', $client->id);
    }

    public function test_update_updates_client()
    {
        $user = $this->authUser();

        $client = VpnClient::factory()->create([
            'user_id' => $user->id,
            'client_name' => 'old-name',
            'status' => 'pending',
        ]);

        $this->putJson("/api/vpn-clients/{$client->id}", [
            'client_name' => 'new-name',
            'status' => 'active',
        ])->assertOk();

        $this->assertDatabaseHas('vpn_clients', [
            'id' => $client->id,
            'client_name' => 'new-name',
            'status' => 'active',
        ]);
    }

    public function test_delete_deletes_client_fails()
    {
        $user = $this->authUser();

        $client = VpnClient::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->deleteJson("/api/vpn-clients/{$client->id}")
            ->assertForbidden(); // only admins can delete

        $this->assertDatabaseHas('vpn_clients', [
            'id' => $client->id,
        ]);
    }

    public function test_delete_deletes_client_pass()
    {
        $user = $user ??= User::factory()->create([
            'role' => 'admin',
        ]);
        Sanctum::actingAs($user);

        $client = VpnClient::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->deleteJson("/api/vpn-clients/{$client->id}")
            ->assertNoContent(); // only admins can delete

        $this->assertDatabaseMissing('vpn_clients', [
            'id' => $client->id,
        ]);
    }

    public function test_cannot_access_other_users_client_if_you_enforce_ownership()
    {
        // Only keep this test if your controller/policies enforce ownership
        $userA = $this->authUser();
        $userB = User::factory()->create();

        $clientOfB = VpnClient::factory()->create([
            'user_id' => $userB->id,
        ]);

        $this->getJson("/api/vpn-clients/$clientOfB->id")
            ->assertStatus(404);
    }
}

