<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Contribution;
use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_syndic_can_generate_invoice_for_contribution()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $user = \App\Models\User::create(['name' => 'Syndic', 'email' => 'syndic@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic = \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2026, 'is_valid' => 0]);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 1000, 'montant_mensuel' => 83.33]);

        $response = $this->actingAs($user)->postJson('/contributions/' . $contribution->id . '/invoices');

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', ['contribution_id' => $contribution->id]);
    }

    public function test_syndic_cannot_generate_invoice_for_other_property()
    {
        $property1 = Property::create(['name' => 'A', 'address' => '1', 'city' => 'X', 'postal_code' => '00000']);
        $property2 = Property::create(['name' => 'B', 'address' => '2', 'city' => 'Y', 'postal_code' => '11111']);
        $user1 = \App\Models\User::create(['name' => 'Syndic1', 'email' => 'syndic1@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        \App\Models\Syndic::create(['user_id' => $user1->id, 'property_id' => $property1->id]);
        $user2 = \App\Models\User::create(['name' => 'Syndic2', 'email' => 'syndic2@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic2 = \App\Models\Syndic::create(['user_id' => $user2->id, 'property_id' => $property2->id]);
        $budget = Budget::create(['property_id' => $property2->id, 'syndic_id' => $syndic2->id, 'year' => 2026, 'is_valid' => 0]);
        $owner = Owner::create(['property_id' => $property2->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 500, 'montant_mensuel' => 41.67]);

        $response = $this->actingAs($user1)->postJson('/contributions/' . $contribution->id . '/invoices');

        $response->assertStatus(403);
    }

    public function test_syndic_can_mark_invoice_as_paid()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $user = \App\Models\User::create(['name' => 'Syndic3', 'email' => 'syndic3@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic = \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2026, 'is_valid' => 0]);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 2000, 'montant_mensuel' => 166.67]);
        $invoice = Invoice::create(['contribution_id' => $contribution->id, 'invoice_number' => 'INV-1', 'issue_date' => now(), 'amount' => 2000, 'status' => 'issued']);

        $response = $this->actingAs($user)->patchJson('/invoices/' . $invoice->id . '/pay');

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_owner_can_view_own_invoices()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $ownerUser = \App\Models\User::create(['name' => 'OwnerUser', 'email' => 'owner_user@example.test', 'password' => bcrypt('secret'), 'role' => 'copropriétaire']);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $ownerUser->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $user4 = \App\Models\User::create(['name' => 'SyndicView', 'email' => 'syndic_view@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic4 = \App\Models\Syndic::create(['user_id' => $user4->id, 'property_id' => $property->id]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic4->id, 'year' => 2026, 'is_valid' => 0]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 1000, 'montant_mensuel' => 83.33]);
        $invoice = Invoice::create(['contribution_id' => $contribution->id, 'owner_id' => $owner->id, 'property_id' => $property->id, 'invoice_number' => 'INV-2', 'issue_date' => now(), 'amount' => 1000, 'status' => 'issued']);

        $response = $this->actingAs($ownerUser)->getJson('/owners/' . $owner->id . '/invoices');

        $response->assertStatus(200);
        $response->assertJsonFragment(['invoice_number' => 'INV-2']);
    }

    public function test_overdue_invoice_reminder_sent_after_30_days()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $user = \App\Models\User::create(['name' => 'Syndic4', 'email' => 'syndic4@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic = \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2025, 'is_valid' => 0]);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 1200, 'montant_mensuel' => 100]);
        $invoice = Invoice::create(['contribution_id' => $contribution->id, 'invoice_number' => 'INV-OLD', 'issue_date' => now()->subDays(40), 'amount' => 1200, 'status' => 'issued', 'created_at' => now()->subDays(40)]);

        $this->artisan('invoice:send-overdue-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        $fresh = Invoice::find($invoice->id);
        $this->assertNotNull($fresh->reminder_sent_at);
    }

    public function test_overdue_invoice_reminder_not_sent_before_30_days()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $user = \App\Models\User::create(['name' => 'Syndic5', 'email' => 'syndic5@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic = \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2026, 'is_valid' => 0]);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 600, 'montant_mensuel' => 50]);
        $invoice = Invoice::create(['contribution_id' => $contribution->id, 'invoice_number' => 'INV-NEW', 'issue_date' => now()->subDays(10), 'amount' => 600, 'status' => 'issued', 'created_at' => now()->subDays(10)]);

        $this->artisan('invoice:send-overdue-reminders')->assertExitCode(0);

        $fresh = Invoice::find($invoice->id);
        $this->assertNull($fresh->reminder_sent_at);
    }

    public function test_overdue_invoice_reminder_not_sent_twice()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $user = \App\Models\User::create(['name' => 'Syndic6', 'email' => 'syndic6@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        $syndic = \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2025, 'is_valid' => 0]);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 1, 'montant_annuel' => 800, 'montant_mensuel' => 66.67]);
        $invoice = Invoice::create(['contribution_id' => $contribution->id, 'invoice_number' => 'INV-OLD2', 'issue_date' => now()->subDays(40), 'amount' => 800, 'status' => 'issued', 'created_at' => now()->subDays(40), 'reminder_sent_at' => now()->subDays(31)]);

        $this->artisan('invoice:send-overdue-reminders')->assertExitCode(0);

        $fresh = Invoice::find($invoice->id);
        $this->assertNotNull($fresh->reminder_sent_at);
    }
}
