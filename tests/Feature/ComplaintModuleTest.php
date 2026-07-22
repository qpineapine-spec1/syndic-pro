<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwnerContext(): array
    {
        $user = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        $property = Property::create([
            'name' => 'Immeuble Réclamation',
            'address' => '5 Rue Test',
        ]);

        $owner = Owner::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'lot_surface' => 100,
            'surface_confirmation' => 100,
        ]);

        return [$user, $owner, $property];
    }

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create([
            'name' => 'Immeuble Réclamation Syndic',
            'address' => '6 Rue Test',
        ]);

        Syndic::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return [$user, $property];
    }

    public function test_owner_can_create_complaint_for_own_property(): void
    {
        [$user, $owner, $property] = $this->makeOwnerContext();

        $response = $this->actingAs($user)->post('/complaints', [
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'subject' => 'Fuite eau',
            'description' => 'Fuite dans la salle commune',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('complaints', ['subject' => 'Fuite eau', 'owner_id' => $owner->id, 'property_id' => $property->id]);
    }

    public function test_owner_cannot_see_other_owners_complaints(): void
    {
        // Owner A
        [$userA, $ownerA, $property] = $this->makeOwnerContext();

        // Owner B on same property
        $userB = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $ownerB = Owner::create(['property_id' => $property->id, 'user_id' => $userB->id, 'lot_surface' => 50, 'surface_confirmation' => 50]);

        // Owner B files a complaint
        Complaint::create(['owner_id' => $ownerB->id, 'property_id' => $property->id, 'subject' => 'Voisin bruyant', 'description' => '...', 'status' => 'open', 'priority' => 'normal']);

        // Owner A fetches complaints
        $response = $this->actingAs($userA)->get('/complaints');
        $response->assertStatus(200);
        $response->assertViewHas('complaints', function ($complaints) use ($ownerA) {
            foreach ($complaints as $c) {
                if ($c->owner_id !== $ownerA->id) {
                    return false;
                }
            }
            return true;
        });
    }

    public function test_syndic_can_see_all_complaints_for_own_property_only(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();

        // Create two complaints for this property
        $owner1 = Owner::create(['property_id' => $property->id, 'user_id' => null, 'lot_surface' => 100, 'surface_confirmation' => 100]);
        Complaint::create(['owner_id' => $owner1->id, 'property_id' => $property->id, 'subject' => 'Problème 1', 'status' => 'open', 'priority' => 'normal']);

        // Another property and complaint
        $other = Property::create(['name' => 'Autre', 'address' => '7 Rue']);
        $ownerOther = Owner::create(['property_id' => $other->id, 'user_id' => null, 'lot_surface' => 50, 'surface_confirmation' => 50]);
        Complaint::create(['owner_id' => $ownerOther->id, 'property_id' => $other->id, 'subject' => 'Problème autre', 'status' => 'open', 'priority' => 'normal']);

        $response = $this->actingAs($syndicUser)->get('/complaints');
        $response->assertStatus(200);
        $response->assertViewHas('complaints', function ($complaints) use ($property) {
            foreach ($complaints as $c) {
                if ($c->property_id !== $property->id) {
                    return false;
                }
            }
            return true;
        });
    }

    public function test_syndic_can_update_complaint_status(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();

        $owner = Owner::create(['property_id' => $property->id, 'user_id' => null, 'lot_surface' => 80, 'surface_confirmation' => 80]);
        $complaint = Complaint::create(['owner_id' => $owner->id, 'property_id' => $property->id, 'subject' => 'Réclamation', 'status' => 'open', 'priority' => 'normal']);

        $response = $this->actingAs($syndicUser)->post('/complaints/' . $complaint->id . '/status', ['status' => 'en_cours']);
        $response->assertStatus(302);

        $complaint->refresh();
        $this->assertEquals('en_cours', $complaint->status);
    }

    public function test_fichier_joint_upload_on_complaint(): void
    {
        Storage::fake('public');

        [$user, $owner, $property] = $this->makeOwnerContext();

        $complaint = Complaint::create(['owner_id' => $owner->id, 'property_id' => $property->id, 'subject' => 'Cassé', 'status' => 'open', 'priority' => 'normal']);

        $file = UploadedFile::fake()->create('photo.jpg', 50);

        $response = $this->actingAs($user)->post('/complaints/' . $complaint->id . '/upload', ['fichier_joint' => $file]);
        $response->assertStatus(302);

        $complaint->refresh();
        $this->assertNotNull($complaint->fichier_joint);
        Storage::disk('public')->assertExists($complaint->fichier_joint);
    }
}
