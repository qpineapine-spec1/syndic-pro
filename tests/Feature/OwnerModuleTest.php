<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create([
            'name' => 'Owner Test Property',
            'address' => '10 Rue Test',
        ]);

        Syndic::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return [$user, $property];
    }

    public function test_syndic_can_view_owners_index(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $ownerUser = User::factory()->create([
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser->id,
            'status' => 'proprietaire',
            'lot_surface' => 45,
            'surface_confirmation' => 45,
            'is_tenant' => false,
            'has_mezzanine' => false,
            'office_number' => null,
            'floor' => null,
        ]);

        $response = $this->actingAs($user)->get(route('owners.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Liste des copropriétaires');
        $response->assertSeeText('Jean Dupont');
        $response->assertSeeText('jean@example.com');
        $response->assertSeeText('Propriétaire');
    }

    public function test_syndic_sees_only_owners_for_their_property(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $otherSyndic = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $otherProperty = Property::create([
            'name' => 'Other Property',
            'address' => '2 Rue autre',
        ]);

        Syndic::create([
            'user_id' => $otherSyndic->id,
            'property_id' => $otherProperty->id,
        ]);

        $ownerUser = User::factory()->create([
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser->id,
            'status' => 'proprietaire',
            'lot_surface' => 45,
            'surface_confirmation' => 45,
            'is_tenant' => false,
            'has_mezzanine' => false,
            'office_number' => null,
            'floor' => null,
        ]);

        $otherOwnerUser = User::factory()->create([
            'name' => 'Pierre Martin',
            'email' => 'pierre@example.com',
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        Owner::create([
            'property_id' => $otherProperty->id,
            'user_id' => $otherOwnerUser->id,
            'status' => 'proprietaire',
            'lot_surface' => 60,
            'surface_confirmation' => 60,
            'is_tenant' => false,
            'has_mezzanine' => false,
            'office_number' => null,
            'floor' => null,
        ]);

        $response = $this->actingAs($user)->get(route('owners.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Jean Dupont');
        $response->assertDontSeeText('Pierre Martin');
        $response->assertDontSeeText('pierre@example.com');
    }

    public function test_owner_role_cannot_access_owners_index(): void
    {
        $ownerUser = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($ownerUser)->get(route('owners.index'));

        $response->assertStatus(403);
    }
}
