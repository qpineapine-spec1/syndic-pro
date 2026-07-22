<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\Owner;
use App\Models\OwnerInvitation;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OwnerStatusAndProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that activation with 'proprietaire' status stores 'proprietaire' in DB (not 'actif')
     */
    public function test_activation_with_proprietaire_stores_correct_status()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Test Prop', 'address' => '1 Test Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'owner@example.com',
            'property_id' => $property->id,
        ])->assertStatus(201);

        $token = null;
        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        $data = [
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'has_mezzanine' => false,
        ];

        $this->post('/activate/' . $token, $data)->assertRedirect(route('login'));

        // Verify status is stored as 'proprietaire', not 'actif'
        $this->assertDatabaseHas('owners', [
            'property_id' => $property->id,
            'status' => 'proprietaire',
        ]);
    }

    /**
     * Test that activation with 'locataire' status stores 'locataire' in DB (not 'inactif')
     */
    public function test_activation_with_locataire_stores_correct_status()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Test Prop 2', 'address' => '2 Test Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'tenant@example.com',
            'property_id' => $property->id,
        ])->assertStatus(201);

        $token = null;
        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        $data = [
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'status' => 'locataire',
            'is_tenant' => true,
            'lot_surface' => 40,
            'surface_confirmation' => 40,
            'has_mezzanine' => false,
        ];

        $this->post('/activate/' . $token, $data)->assertRedirect(route('login'));

        // Verify status is stored as 'locataire', not 'inactif'
        $this->assertDatabaseHas('owners', [
            'property_id' => $property->id,
            'status' => 'locataire',
        ]);
    }

    /**
     * Test that mezzanine_surface is required when has_mezzanine is true (server-side validation)
     */
    public function test_mezzanine_surface_required_when_has_mezzanine_true()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Test Prop 3', 'address' => '3 Test Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'mezzanine@example.com',
            'property_id' => $property->id,
        ])->assertStatus(201);

        $token = null;
        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        // Try to activate without mezzanine_surface when has_mezzanine is true
        $data = [
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'name' => 'Mezzanine Owner',
            'email' => 'mezzanine@example.com',
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 60,
            'surface_confirmation' => 60,
            'has_mezzanine' => true,
            // mezzanine_surface is missing
        ];

        $response = $this->post('/activate/' . $token, $data);
        $response->assertSessionHasErrors('mezzanine_surface');
    }

    /**
     * Test that mezzanine_surface is optional when has_mezzanine is false
     */
    public function test_mezzanine_surface_optional_when_has_mezzanine_false()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Test Prop 4', 'address' => '4 Test Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $this->actingAs($syndicUser)->postJson('/invitations', [
            'email' => 'nomezzanine@example.com',
            'property_id' => $property->id,
        ])->assertStatus(201);

        $token = null;
        Mail::assertSent(AccountActivationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        $data = [
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'name' => 'No Mezzanine Owner',
            'email' => 'nomezzanine@example.com',
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 55,
            'surface_confirmation' => 55,
            'has_mezzanine' => false,
        ];

        $response = $this->post('/activate/' . $token, $data);
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('owners', [
            'property_id' => $property->id,
            'has_mezzanine' => false,
        ]);
    }

    /**
     * Test that ProfileController@update only modifies own Owner (anti-IDOR)
     */
    public function test_profile_update_anti_idor_protection()
    {
        // Create two owners
        $property = Property::create(['name' => 'Test Prop 5', 'address' => '5 Test Rue']);

        $owner1User = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner1 = Owner::create([
            'property_id' => $property->id,
            'user_id' => $owner1User->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'has_mezzanine' => false,
        ]);

        $owner2User = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner2 = Owner::create([
            'property_id' => $property->id,
            'user_id' => $owner2User->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 60,
            'surface_confirmation' => 60,
            'has_mezzanine' => false,
        ]);

        // Owner1 attempts to update their own profile - should succeed
        $response = $this->actingAs($owner1User)->patch('/profile', [
            'name' => 'Updated Owner 1',
            'lot_surface' => 55,
            'surface_confirmation' => 55,
            'has_mezzanine' => false,
        ]);
        $response->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('owners', [
            'id' => $owner1->id,
            'lot_surface' => 55,
        ]);

        // Owner1 attempts to update owner2's profile via direct manipulation - should only affect their own
        // (Since we don't expose ID in the route, this test verifies they can only access their own profile)
        $owner2OwnedBefore = $owner2->fresh()->lot_surface;
        $response = $this->actingAs($owner1User)->patch('/profile', [
            'name' => 'Hacked',
            'lot_surface' => 100,
            'surface_confirmation' => 100,
            'has_mezzanine' => false,
        ]);
        
        // Verify owner1 was updated, not owner2
        $this->assertDatabaseHas('owners', [
            'id' => $owner1->id,
            'lot_surface' => 100,
        ]);
        
        // Verify owner2 was NOT modified
        $this->assertDatabaseHas('owners', [
            'id' => $owner2->id,
            'lot_surface' => $owner2OwnedBefore,
        ]);
    }

    /**
     * Test that status is displayed as "Propriétaire" / "Locataire" on owners index view
     */
    public function test_owner_status_displayed_correctly_on_index()
    {
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Test Prop 6', 'address' => '6 Test Rue']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $ownerUser1 = User::factory()->create(['role' => 'copropriétaire', 'name' => 'Owner One', 'email_verified_at' => now()]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser1->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'has_mezzanine' => false,
        ]);

        $ownerUser2 = User::factory()->create(['role' => 'copropriétaire', 'name' => 'Owner Two', 'email_verified_at' => now()]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser2->id,
            'status' => 'locataire',
            'is_tenant' => true,
            'lot_surface' => 40,
            'surface_confirmation' => 40,
            'has_mezzanine' => false,
        ]);

        $response = $this->actingAs($syndicUser)->get('/owners');
        $response->assertStatus(200);
        
        // Verify displayed labels, not database values
        $response->assertSee('Propriétaire');
        $response->assertSee('Locataire');
        $response->assertDontSee('actif');
        $response->assertDontSee('inactif');
    }

    /**
     * Test that profile update validation includes required_if for mezzanine_surface
     */
    public function test_profile_update_mezzanine_validation()
    {
        $property = Property::create(['name' => 'Test Prop 7', 'address' => '7 Test Rue']);

        $ownerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'has_mezzanine' => false,
        ]);

        // Try to update with has_mezzanine=true but no mezzanine_surface
        $response = $this->actingAs($ownerUser)->patch('/profile', [
            'name' => 'Updated Owner',
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'has_mezzanine' => true,
            // Missing mezzanine_surface
        ]);

        $response->assertSessionHasErrors('mezzanine_surface');
    }
}
