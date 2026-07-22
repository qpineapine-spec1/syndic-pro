<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\OwnerInvitation;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_syndic_can_create_invitation_and_mail_sent()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Inv Prop', 'address' => '1 Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $response = $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'newowner@example.com',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('owner_invitations', ['email' => 'newowner@example.com', 'property_id' => $property->id]);

        Mail::assertSent(AccountActivationMail::class);
    }

    public function test_invitation_rejected_if_email_exists()
    {
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Inv Prop 2', 'address' => '2 Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        // Existing user with that email
        User::factory()->create(['email' => 'exists@example.com']);

        $response = $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'exists@example.com',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_activation_via_invitation_creates_user_and_owner()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Inv Prop 3', 'address' => '3 Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $resp = $this->actingAs($syndicUser)->postJson('/invitations', ['email' => 'future@example.com', 'property_id' => $property->id]);
        $resp->assertStatus(201);

        $inv = OwnerInvitation::first();
        $token = 'token-placeholder';
        // We can't retrieve raw token from DB (only hash stored), but AccountActivationMail was sent with token.
        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        $data = [
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'name' => 'New Owner',
            'email' => 'future@example.com',
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 45,
            'surface_confirmation' => 45,
            'has_mezzanine' => false,
        ];

        $activateResponse = $this->post('/activate/' . $token, $data);
        $activateResponse->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'future@example.com']);
        $this->assertDatabaseHas('owners', ['property_id' => $property->id]);
    }

    public function test_owner_activation_page_displays_invitation_email()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Inv Prop 5', 'address' => '5 Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'invitee@example.com',
            'property_id' => $property->id,
        ])->assertStatus(201);

        $token = null;
        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        $response = $this->get('/activate/' . $token);
        $response->assertStatus(200);
        $response->assertSee('Activation copropriétaire');
        $response->assertSee('name="email"', false);
        $response->assertSee('value="invitee@example.com"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('Propriétaire', false);
        $response->assertSee('Locataire', false);
        $response->assertDontSee('name="is_tenant"', false);
    }

    public function test_activation_with_expired_token_is_invalid()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Inv Prop 4', 'address' => '4 Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $this->actingAs($syndicUser)->postJson('/invitations', ['email' => 'late@example.com', 'property_id' => $property->id]);
        $inv = OwnerInvitation::first();

        // Simulate expiry
        $inv->expires_at = now()->subHours(1);
        $inv->save();

        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token; return true; });

        $response = $this->post('/activate/' . $token, [
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'name' => 'Late',
            'email' => 'late@example.com',
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 30,
            'surface_confirmation' => 30,
            'has_mezzanine' => false,
        ]);

        $response->assertSeeText('Ce lien est expiré');
    }

}
