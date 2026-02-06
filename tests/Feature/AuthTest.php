<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token_and_creates_user()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $res = $this->postJson('/api/auth/register', $payload);

        $res->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_login_returns_token_for_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $res->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $this->assertEquals($user->id, $res->json('user.id'));
    }

    public function test_me_requires_authentication()
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_me_works_with_bearer_token()
    {
        $user = User::factory()->create();

        $token = $user->createToken('api')->plainTextToken;

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_logout_revokes_current_token()
    {
        $user = User::factory()->create();

        $plain = $user->createToken('api')->plainTextToken;

        $tokenRow = PersonalAccessToken::findToken($plain);
        $this->assertNotNull($tokenRow, 'Token row not found right after creation');
        $tokenId = $tokenRow->id;

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer $plain",
            'Accept' => 'application/json',
        ])->assertOk();

        // ✅ this is the real check
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        Auth::forgetGuards(); // auth guard keeps a cached login and since tests share the same process, we need to clear it. HTTP requests do this automatically.
        // Now Sanctum should NOT authenticate
        $res = $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer $plain",
            'Accept' => 'application/json',
        ]);

        $rows = DB::table('personal_access_tokens')->get();
        fwrite(STDERR, "\nTOKENS after logout (from test): " . $rows->toJson() . "\n");

        $res->assertStatus(401);
    }
}
