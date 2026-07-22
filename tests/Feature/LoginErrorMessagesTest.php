<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_displays_validation_errors_for_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        // Follow redirect to see if errors are displayed
        $loginPage = $this->get('/login');
        $loginPage->assertSeeText('Les identifiants fournis sont incorrects.');
    }

    public function test_login_page_shows_validation_error_for_missing_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'somepassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_login_page_shows_validation_error_for_missing_password(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');
    }

    public function test_login_page_shows_validation_error_for_invalid_email_format(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => 'somepassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_login_with_correct_credentials_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_shows_error(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'password' => bcrypt('CorrectPassword123!'),
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'existing@example.com',
            'password' => 'WrongPassword456!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $loginPage = $this->get('/login');
        $loginPage->assertSeeText('Les identifiants fournis sont incorrects.');
    }

    public function test_login_with_unactivated_account_redirects_to_activation_pending(): void
    {
        $user = User::factory()->create([
            'email' => 'unactivated@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'copropriétaire',
            'email_verified_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'unactivated@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('activation.pending', ['email' => 'unactivated@example.com']));
    }
}