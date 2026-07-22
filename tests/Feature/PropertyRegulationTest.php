<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyRegulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_welcome_shows_grayed_when_no_reglement(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('Règlement pas encore disponible');
    }

    public function test_syndic_can_upload_reglement_and_anti_idor(): void
    {
        Storage::fake('public');

        $syndic = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Prop A', 'address' => '1 Rue A']);
        Syndic::create(['user_id' => $syndic->id, 'property_id' => $property->id]);

        $file = UploadedFile::fake()->create('reglement.pdf', 100, 'application/pdf');

        // Expect route to exist and accept upload for own property even before first-assembly import
        $resp = $this->actingAs($syndic)->post(route('properties.reglement.upload', $property), ['reglement' => $file]);
        $resp->assertStatus(302);

        $property->refresh();
        $this->assertNotNull($property->reglement_fichier);
        Storage::disk('public')->assertExists($property->reglement_fichier);
        $this->assertNull($property->imported_at);

        // Other syndic cannot upload to this property
        $other = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $resp2 = $this->actingAs($other)->post(route('properties.reglement.upload', $property), ['reglement' => $file]);
        $resp2->assertStatus(403);
    }
}
