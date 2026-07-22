<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouncilMemberModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeCouncilOwnerContext(): array
    {
        $property = Property::create(['name' => 'Immeuble Conseil', 'address' => '10 Rue Conseil']);
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner = Owner::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'is_council_member' => true,
        ]);

        return [$user, $owner, $property];
    }

    private function makeOwnerContextForProperty(Property $property, bool $isCouncilMember = false): array
    {
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner = Owner::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'is_council_member' => $isCouncilMember,
        ]);

        return [$user, $owner];
    }

    public function test_council_member_can_list_expenses_of_their_property(): void
    {
        [$user, $owner, $property] = $this->makeCouncilOwnerContext();

        Expense::create([
            'property_id' => $property->id,
            'label' => 'Electricité',
            'amount' => 200.00,
            'expense_date' => now()->toDateString(),
            'type' => 'charge',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/expenses');
        $response->assertStatus(200);
        $response->assertViewHas('expenses', function ($expenses) use ($property) {
            return $expenses->count() === 1 && $expenses->first()->property_id === $property->id;
        });
    }

    public function test_council_member_cannot_create_modify_or_delete_expense(): void
    {
        [$user, $owner, $property] = $this->makeCouncilOwnerContext();

        $response = $this->actingAs($user)->post('/expenses', [
            'property_id' => $property->id,
            'label' => 'Entretien',
            'amount' => 120.00,
            'expense_date' => now()->toDateString(),
            'type' => 'charge',
            'category' => 'service',
        ]);

        $response->assertStatus(403);
    }

    public function test_non_council_owner_cannot_see_expenses_of_other_property(): void
    {
        $propertyA = Property::create(['name' => 'Immeuble A', 'address' => '1 Rue A']);
        [$userA, $ownerA] = $this->makeOwnerContextForProperty($propertyA, false);
        $propertyB = Property::create(['name' => 'Immeuble B', 'address' => '2 Rue B']);
        Expense::create([
            'property_id' => $propertyB->id,
            'label' => 'Eau',
            'amount' => 80.00,
            'expense_date' => now()->toDateString(),
            'type' => 'charge',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($userA)->get('/expenses');
        $response->assertStatus(200);
        $response->assertViewHas('expenses', function ($expenses) use ($propertyA) {
            return $expenses->count() === 0;
        });
    }

    public function test_council_member_sees_only_expenses_of_their_property(): void
    {
        [$user, $owner, $property] = $this->makeCouncilOwnerContext();
        $otherProperty = Property::create(['name' => 'Immeuble Autre', 'address' => '3 Rue Autre']);

        Expense::create([
            'property_id' => $property->id,
            'label' => 'Chauffage',
            'amount' => 300.00,
            'expense_date' => now()->toDateString(),
            'type' => 'charge',
            'status' => 'pending',
        ]);

        Expense::create([
            'property_id' => $otherProperty->id,
            'label' => 'Nettoyage',
            'amount' => 150.00,
            'expense_date' => now()->toDateString(),
            'type' => 'service',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/expenses');
        $response->assertStatus(200);
        $response->assertViewHas('expenses', function ($expenses) use ($property) {
            return $expenses->count() === 1 && $expenses->first()->property_id === $property->id;
        });
    }

    public function test_council_member_can_see_all_invoices_of_property_and_owner_sees_only_their_invoices(): void
    {
        $property = Property::create(['name' => 'Immeuble Factures', 'address' => '4 Rue Factures']);

        $userCouncil = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $ownerCouncil = Owner::create([
            'property_id' => $property->id,
            'user_id' => $userCouncil->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 60,
            'surface_confirmation' => 60,
            'is_council_member' => true,
        ]);

        $userOther = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $ownerOther = Owner::create([
            'property_id' => $property->id,
            'user_id' => $userOther->id,
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 40,
            'surface_confirmation' => 40,
            'is_council_member' => false,
        ]);

        // Create a syndic, budget and contributions for the property so invoices can reference a contribution
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $syndic = Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $budget = \App\Models\Budget::create([
            'property_id' => $property->id,
            'syndic_id' => $syndic->id,
            'year' => now()->year,
            'fixed_charges_total' => 0,
            'variable_charges_total' => 0,
        ]);

        $contrib1 = \App\Models\Contribution::create([
            'owner_id' => $ownerCouncil->id,
            'budget_id' => $budget->id,
            'tantieme' => 1,
            'quote_part_terrain' => 0,
            'montant_annuel' => 1200,
            'montant_mensuel' => 100,
            'charges_surplus' => 0,
            'status' => 'a_jour',
        ]);

        $contrib2 = \App\Models\Contribution::create([
            'owner_id' => $ownerOther->id,
            'budget_id' => $budget->id,
            'tantieme' => 1,
            'quote_part_terrain' => 0,
            'montant_annuel' => 1200,
            'montant_mensuel' => 100,
            'charges_surplus' => 0,
            'status' => 'a_jour',
        ]);

        \App\Models\Invoice::create([
            'contribution_id' => $contrib1->id,
            'owner_id' => $ownerCouncil->id,
            'property_id' => $property->id,
            'invoice_number' => 'INV-100',
            'issue_date' => now()->toDateString(),
            'due_date' => null,
            'amount' => 100.00,
            'status' => 'non_payee',
        ]);

        \App\Models\Invoice::create([
            'contribution_id' => $contrib2->id,
            'owner_id' => $ownerOther->id,
            'property_id' => $property->id,
            'invoice_number' => 'INV-101',
            'issue_date' => now()->toDateString(),
            'due_date' => null,
            'amount' => 150.00,
            'status' => 'non_payee',
        ]);

        $response = $this->actingAs($userCouncil)->get('/invoices');
        $response->assertStatus(200);
        $response->assertViewHas('invoices', function ($invoices) {
            return $invoices->count() === 2;
        });

        $response2 = $this->actingAs($userOther)->get('/invoices');
        $response2->assertStatus(200);
        $response2->assertViewHas('invoices', function ($invoices) use ($ownerOther) {
            return $invoices->count() === 1 && $invoices->first()->owner_id === $ownerOther->id;
        });
    }
}
