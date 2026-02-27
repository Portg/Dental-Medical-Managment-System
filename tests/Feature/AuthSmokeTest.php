<?php

namespace Tests\Feature;

use App\Branch;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private string $password = 'password';

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $adminRole  = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt($this->password),
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt($this->password),
        ]);
    }

    // ─── Web (session) auth ────────────────────────────────────────

    public function test_login_page_loads(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_web_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email'    => $this->admin->email,
            'password' => $this->password,
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_web_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email'    => $this->admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_web_logout(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/home')->assertRedirect('/login');
        $this->get('/patients')->assertRedirect('/login');
        $this->get('/appointments')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_home(): void
    {
        // Admin role now redirects /home to /today-work
        $this->actingAs($this->admin)
             ->get('/home')
             ->assertRedirect('today-work');
    }

    // ─── API (Sanctum) auth ────────────────────────────────────────

    public function test_api_login_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->admin->email,
            'password' => $this->password,
        ]);

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => ['token', 'token_type', 'user'],
                     'message',
                 ])
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.user.email', $this->admin->email);
    }

    public function test_api_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->admin->email,
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('success', false);
    }

    public function test_api_login_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    public function test_api_me_returns_user_info(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/v1/auth/me');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.email', $this->admin->email)
                 ->assertJsonPath('data.role', 'Administrator');
    }

    public function test_api_logout_revokes_token(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/v1/auth/logout')
             ->assertOk()
             ->assertJsonPath('success', true);

        // Refresh app to clear Sanctum's token cache
        $this->refreshApplication();

        // Token should now be invalid
        $this->withHeader('Authorization', "Bearer {$token}")
             ->getJson('/api/v1/auth/me')
             ->assertStatus(401);
    }

    public function test_api_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->getJson('/api/v1/patients')->assertStatus(401);
    }

    // ─── Role gate checks ──────────────────────────────────────────

    public function test_admin_gate_allows_administrator(): void
    {
        $this->assertTrue($this->admin->can('Admin-Dashboard'));
        $this->assertFalse($this->doctor->can('Admin-Dashboard'));
    }

    public function test_doctor_gate_allows_doctor(): void
    {
        $this->assertTrue($this->doctor->can('Doctor-Dashboard'));
        $this->assertFalse($this->admin->can('Doctor-Dashboard'));
    }
}
