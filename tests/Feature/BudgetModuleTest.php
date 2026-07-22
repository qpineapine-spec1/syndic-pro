<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Contribution;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use App\Services\BudgetPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create([
            'name' => 'Immeuble Budget Test',
            'address' => '10 Rue Test',
        ]);

        Syndic::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return [$user, $property];
    }

    public function test_syndic_can_create_and_validate_a_budget(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $response = $this->actingAs($user)->post('/budgets', [
            'year' => 2026,
            'fixed_charges_total' => 1000,
            'variable_charges_total' => 500,
        ]);

        $response->assertRedirect(route('budgets.index'));

        $budget = Budget::first();
        $this->assertNotNull($budget);
        $this->assertFalse((bool) $budget->is_valid);

        // Validate via endpoint
        $validateResp = $this->actingAs($user)->post(route('budgets.validate', $budget));
        $validateResp->assertRedirect(route('budgets.index'));

        $budget->refresh();
        $this->assertTrue((bool) $budget->is_valid);

        // IMPORTANT: validating a budget must NOT create contributions automatically
        $this->assertDatabaseCount('contributions', 0);
    }

    public function test_prediction_unavailable_with_insufficient_history(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $service = $this->app->make(BudgetPredictionService::class);

        // 0 budgets
        $this->assertFalse($service->isPredictionAvailable($property));

        // 1 valid budget
        Budget::create([
            'property_id' => $property->id,
            'syndic_id' => $user->syndic->id,
            'year' => now()->year,
            'is_valid' => true,
            'fixed_charges_total' => 1000,
            'variable_charges_total' => 200,
        ]);

        $this->assertFalse($service->isPredictionAvailable($property));

        // 2 valid budgets
        Budget::create([
            'property_id' => $property->id,
            'syndic_id' => $user->syndic->id,
            'year' => now()->subYear()->year,
            'is_valid' => true,
            'fixed_charges_total' => 1100,
            'variable_charges_total' => 250,
        ]);

        $this->assertFalse($service->isPredictionAvailable($property));

        // Ensure no prediction_xgboost generated (all budgets keep prediction_xgboost NULL)
        $this->assertEquals(0, Budget::where('property_id', $property->id)->whereNotNull('prediction_xgboost')->count());
    }

    public function test_prediction_available_with_three_consecutive_valid_years(): void
    {
        [$user, $property] = $this->makeSyndicContext();

        $service = $this->app->make(BudgetPredictionService::class);

        $y0 = now()->year;
        $y1 = $y0 - 1;
        $y2 = $y0 - 2;

        // three consecutive valid budgets
        Budget::create(['property_id' => $property->id, 'syndic_id' => $user->syndic->id, 'year' => $y2, 'is_valid' => true, 'fixed_charges_total' => 100, 'variable_charges_total' => 50]);
        Budget::create(['property_id' => $property->id, 'syndic_id' => $user->syndic->id, 'year' => $y1, 'is_valid' => true, 'fixed_charges_total' => 200, 'variable_charges_total' => 60]);
        Budget::create(['property_id' => $property->id, 'syndic_id' => $user->syndic->id, 'year' => $y0, 'is_valid' => true, 'fixed_charges_total' => 300, 'variable_charges_total' => 70]);

        $this->assertTrue($service->isPredictionAvailable($property));

        // Negative case: 3 valid budgets but not consecutive
        $property2 = Property::create(['name' => 'Immeuble Test 2', 'address' => '2 Rue']);
        Syndic::create(['user_id' => $user->id, 'property_id' => $property2->id]);

        Budget::create(['property_id' => $property2->id, 'syndic_id' => $user->syndic->id, 'year' => 2022, 'is_valid' => true, 'fixed_charges_total' => 100, 'variable_charges_total' => 10]);
        Budget::create(['property_id' => $property2->id, 'syndic_id' => $user->syndic->id, 'year' => 2023, 'is_valid' => true, 'fixed_charges_total' => 100, 'variable_charges_total' => 10]);
        Budget::create(['property_id' => $property2->id, 'syndic_id' => $user->syndic->id, 'year' => 2025, 'is_valid' => true, 'fixed_charges_total' => 100, 'variable_charges_total' => 10]);

        $this->assertFalse($service->isPredictionAvailable($property2));
    }
}
