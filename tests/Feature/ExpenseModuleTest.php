<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create([
            'name' => 'Immeuble Expense Test',
            'address' => '3 Rue Test',
        ]);

        Syndic::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return [$user, $property];
    }

    public function test_syndic_can_create_expense_for_own_property(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $response = $this->actingAs($user)->post('/expenses', [
            'property_id' => $property->id,
            'label' => 'Réparation ascenseur',
            'amount' => 1200.50,
            'expense_date' => now()->toDateString(),
            'type' => 'maintenance',
            'category' => 'ascenseur',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('expenses', ['label' => 'Réparation ascenseur', 'amount' => 1200.50, 'property_id' => $property->id]);
    }

    public function test_syndic_cannot_create_expense_for_another_property(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        // Another property not owned by this syndic
        $other = Property::create(['name' => 'Autre Immeuble', 'address' => '4 Rue']);

        $response = $this->actingAs($user)->post('/expenses', [
            'property_id' => $other->id,
            'label' => 'Travaux',
            'amount' => 500,
            'expense_date' => now()->toDateString(),
            'type' => 'maintenance',
            'category' => 'peinture',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_justificatif_upload_updates_fichier_facture(): void
    {
        Storage::fake('public');

        [$user, $property] = $this->makeSyndicContext();

        $expense = Expense::create([
            'property_id' => $property->id,
            'label' => 'Fourniture',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'type' => 'purchase',
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->create('facture.pdf', 100);

        $response = $this->actingAs($user)->post('/expenses/' . $expense->id . '/upload', [
            'fichier_facture' => $file,
        ]);

        $response->assertStatus(302);

        $expense->refresh();
        $this->assertNotNull($expense->fichier_facture);
        Storage::disk('public')->assertExists($expense->fichier_facture);
    }

    public function test_expense_status_shows_justificatif_manquant_when_absent(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $expense = Expense::create([
            'property_id' => $property->id,
            'label' => 'Nettoyage',
            'amount' => 200,
            'expense_date' => now()->toDateString(),
            'type' => 'service',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/expenses');

        $response->assertStatus(200);
        $response->assertViewHas('expenses', function ($expenses) use ($expense) {
            foreach ($expenses as $e) {
                if ($e->id === $expense->id) {
                    return empty($e->fichier_facture);
                }
            }
            return false;
        });
    }

    public function test_syndic_sees_expense_file_upload_form_per_row(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        Expense::create([
            'property_id' => $property->id,
            'label' => 'Fourniture',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'type' => 'purchase',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/expenses');

        $response->assertStatus(200);
        $response->assertSee('fichier_facture');
        $response->assertSee('Uploader');
    }
}
