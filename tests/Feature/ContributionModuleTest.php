<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Contribution;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create([
            'name' => 'Immeuble Test',
            'address' => '1 Rue Test',
        ]);

        Syndic::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return [$user, $property];
    }

    public function test_syndic_can_view_contributions_page(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $response = $this->actingAs($user)->get('/contributions');

        $response->assertOk();
        $response->assertViewIs('contributions.index');
        $response->assertViewHas('canCalculate', false);
    }

    public function test_calculation_is_blocked_when_an_owner_has_no_surface(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        Owner::create([
            'property_id' => $property->id,
            'user_id' => null,
            'lot_surface' => 0,
            'surface_confirmation' => 0,
        ]);

        Owner::create([
            'property_id' => $property->id,
            'user_id' => null,
            'lot_surface' => 200,
            'surface_confirmation' => 200,
        ]);

        $response = $this->actingAs($user)->post('/contributions/calculate', [
            'property_id' => $property->id,
        ]);

        $response->assertRedirect(route('contributions.index'));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('Superficie manquante', $error);
        $this->assertDatabaseCount('contributions', 0);
    }

    public function test_calculation_uses_tantieme_and_budget_amount_when_surfaces_are_complete(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $budget = Budget::create([
            'property_id' => $property->id,
            'syndic_id' => $user->syndic->id,
            'year' => 2026,
            'is_valid' => true,
            'fixed_charges_total' => 1200,
            'variable_charges_total' => 600,
        ]);

        $ownerOne = Owner::create([
            'property_id' => $property->id,
            'user_id' => null,
            'lot_surface' => 100,
            'surface_confirmation' => 100,
        ]);

        $ownerTwo = Owner::create([
            'property_id' => $property->id,
            'user_id' => null,
            'lot_surface' => 300,
            'surface_confirmation' => 300,
        ]);

        $response = $this->actingAs($user)->post('/contributions/calculate', [
            'property_id' => $property->id,
        ]);

        $response->assertRedirect(route('contributions.index'));
        $response->assertSessionHas('success', 'Les cotisations ont été calculées avec succès.');

        $contributionOne = Contribution::where('owner_id', $ownerOne->id)->where('budget_id', $budget->id)->first();
        $contributionTwo = Contribution::where('owner_id', $ownerTwo->id)->where('budget_id', $budget->id)->first();

        $this->assertNotNull($contributionOne);
        $this->assertNotNull($contributionTwo);
        $this->assertEquals(250.0, (float) $contributionOne->tantieme);
        $this->assertEquals(450.0, (float) $contributionOne->montant_annuel);
        $this->assertEquals(37.5, (float) $contributionOne->montant_mensuel);
        $this->assertEquals(750.0, (float) $contributionTwo->tantieme);
        $this->assertEquals(1350.0, (float) $contributionTwo->montant_annuel);
        $this->assertEquals(112.5, (float) $contributionTwo->montant_mensuel);
    }

    public function test_calculation_is_blocked_when_an_owner_account_is_unactivated(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        // Owner with activated account and complete surface
        $ownerUser1 = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser1->id,
            'lot_surface' => 100,
            'surface_confirmation' => 100,
        ]);

        // Owner with UNACTIVATED account but complete surface
        $ownerUser2 = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => null,
        ]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser2->id,
            'lot_surface' => 200,
            'surface_confirmation' => 200,
        ]);

        $response = $this->actingAs($user)->post('/contributions/calculate', [
            'property_id' => $property->id,
        ]);

        $response->assertRedirect(route('contributions.index'));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('Comptes non activés', $error);
        $this->assertStringContainsString($ownerUser2->name, $error);
        $this->assertDatabaseCount('contributions', 0);
    }

    public function test_calculation_is_blocked_when_surface_and_activation_both_incomplete(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        // Owner with activated account but NO surface
        $ownerUser1 = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser1->id,
            'lot_surface' => null,
            'surface_confirmation' => 0,
        ]);

        // Owner with UNACTIVATED account but with surface
        $ownerUser2 = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => null,
        ]);
        Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser2->id,
            'lot_surface' => 200,
            'surface_confirmation' => 200,
        ]);

        $response = $this->actingAs($user)->post('/contributions/calculate', [
            'property_id' => $property->id,
        ]);

        $response->assertRedirect(route('contributions.index'));
        $response->assertSessionHas('error');
        $error = session('error');
        // Should report BOTH issues (surface checked first, then activation)
        $this->assertStringContainsString('Superficie manquante', $error);
        $this->assertDatabaseCount('contributions', 0);
    }

    public function test_contributions_page_shows_budget_validation_hint_when_no_valid_budget_exists(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $response = $this->actingAs($user)->get('/contributions');

        $response->assertOk();
        $response->assertSee('Aucun budget validé');
        $response->assertSee('Ouvrir la page Budget');
        $response->assertSee(route('budgets.index'));
    }

    public function test_calculation_succeeds_when_all_owners_activated_and_surfaces_complete(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $budget = Budget::create([
            'property_id' => $property->id,
            'syndic_id' => $user->syndic->id,
            'year' => 2026,
            'is_valid' => true,
            'fixed_charges_total' => 1200,
            'variable_charges_total' => 600,
        ]);

        // Owner with activated account and complete surface
        $ownerUser1 = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);
        $ownerOne = Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser1->id,
            'lot_surface' => 100,
            'surface_confirmation' => 100,
        ]);

        // Another owner, also activated and with surface
        $ownerUser2 = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);
        $ownerTwo = Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser2->id,
            'lot_surface' => 300,
            'surface_confirmation' => 300,
        ]);

        $response = $this->actingAs($user)->post('/contributions/calculate', [
            'property_id' => $property->id,
        ]);

        $response->assertRedirect(route('contributions.index'));
        $response->assertSessionHas('success', 'Les cotisations ont été calculées avec succès.');

        $indexResponse = $this->actingAs($user)->get('/contributions');
        $indexResponse->assertOk();
        $indexResponse->assertViewHas('canCalculate', true);

        // Both contributions should be created
        $contributionOne = Contribution::where('owner_id', $ownerOne->id)->where('budget_id', $budget->id)->first();
        $contributionTwo = Contribution::where('owner_id', $ownerTwo->id)->where('budget_id', $budget->id)->first();

        $this->assertNotNull($contributionOne);
        $this->assertNotNull($contributionTwo);
        $this->assertEquals(250.0, (float) $contributionOne->tantieme);
        $this->assertEquals(450.0, (float) $contributionOne->montant_annuel);
    }
}

