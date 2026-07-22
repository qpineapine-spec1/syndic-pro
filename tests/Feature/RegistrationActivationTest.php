<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\AccountActivationToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationActivationTest extends TestCase
{
    public function test_registering_a_syndic_sends_activation_email_and_keeps_account_unverified(): void
    {
        Mail::fake();
        $email = 'activation.' . uniqid() . '@example.com';

        $response = $this->post('/register', [
            'name' => 'Syndic Activation',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'property_name' => 'Immeuble Test',
            'property_address' => '1 Rue Test',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Votre compte syndic a été créé avec succès. Vérifiez votre email pour l’activation.');

        $user = User::where('email', $email)->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull(AccountActivationToken::where('user_id', $user->id)->first());

        Mail::assertSent(AccountActivationMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    public function test_an_unverified_syndic_cannot_log_in_until_activation(): void
    {
        $user = User::factory()->create([
            'email' => 'pending.' . uniqid() . '@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'syndic',
            'email_verified_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('activation.pending', ['email' => $user->email]));
    }

    public function test_the_resend_activation_page_is_accessible(): void
    {
        $response = $this->get('/activate/resend');

        $response->assertOk();
        $response->assertViewIs('auth.resend-activation');
    }
}
