<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'email' => 'new-admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_register_page_returns_ok_with_register_view(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_register_creates_user_and_redirects_to_admin(): void
    {
        $response = $this->post('/register', $this->registerPayload());

        $response->assertRedirect('/admin');
        $user = User::where('email', 'new-admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_register_fails_when_name_is_missing(): void
    {
        $response = $this->post('/register', $this->registerPayload(['name' => '']));

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_register_fails_when_email_is_missing(): void
    {
        $response = $this->post('/register', $this->registerPayload(['email' => '']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_register_fails_when_email_format_is_invalid(): void
    {
        $response = $this->post('/register', $this->registerPayload(['email' => 'not-an-email']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_register_fails_when_email_is_already_registered(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', $this->registerPayload(['email' => 'taken@example.com']));

        $response->assertSessionHasErrors(['email' => 'このメールアドレスは既に登録されています']);
        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
    }

    public function test_register_fails_when_password_is_missing(): void
    {
        $response = $this->post('/register', $this->registerPayload(['password' => '', 'password_confirmation' => '']));

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_register_fails_when_password_is_less_than_eight_characters(): void
    {
        $response = $this->post('/register', $this->registerPayload(['password' => 'abc1234', 'password_confirmation' => 'abc1234']));

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_register_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->post('/register', $this->registerPayload(['password_confirmation' => 'different-password']));

        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    public function test_login_page_returns_ok_with_login_view(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_login_succeeds_with_correct_credentials_and_redirects_to_admin(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => Hash::make('password')]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_login_fails_when_email_is_missing(): void
    {
        $response = $this->post('/login', ['email' => '', 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_login_fails_when_password_is_missing(): void
    {
        $response = $this->post('/login', ['email' => 'admin@example.com', 'password' => '']);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_login_fails_with_unregistered_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_six_failed_attempts_within_a_minute(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => Hash::make('password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong-password']);
        }

        $response = $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('秒後に再度お試しください', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_guest_accessing_admin_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_admin_and_sees_paginated_contacts(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(20)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 20 && $contacts->count() === 7);
    }

    public function test_logout_destroys_session_and_admin_requires_login_again(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();

        $this->get('/admin')->assertRedirect('/login');
    }
}
