<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'user' => ['id', 'email', 'tier']]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'tier'  => 'free',
        ]);
    }

    public function test_register_requires_valid_email(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'not-an-email',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user' => ['id', 'email', 'tier']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ])->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_login_revokes_old_tokens(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $user->createToken('old-token');

        $this->assertEquals(1, $user->tokens()->count());

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(200);

        // Old token gone, new one created
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    public function test_user_can_get_self(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->getJson('/api/auth/me')
             ->assertStatus(200)
             ->assertJsonPath('data.email', $user->email)
             ->assertJsonPath('data.tier', $user->tier);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')
             ->assertStatus(401)
             ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/auth/logout')
             ->assertStatus(204);

        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_user_can_regenerate_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/auth/token/regenerate')
                         ->assertStatus(200)
                         ->assertJsonStructure(['token']);

        $newToken = $response->json('token');
        $this->assertNotEquals($token, $newToken);
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    public function test_inactive_user_is_blocked(): void
    {
        $user  = User::factory()->create(['is_active' => false]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->getJson('/api/auth/me')
             ->assertStatus(403)
             ->assertJsonPath('code', 'ACCOUNT_DEACTIVATED');
    }
}
