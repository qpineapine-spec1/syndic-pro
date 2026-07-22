<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\Budget;
use App\Models\Complaint;
use App\Models\Contribution;
use App\Models\Invoice;
use App\Models\Meeting;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteChoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ModuleCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_shows_unpaid_invoices_and_latest_vote_decision(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble A', 'address' => '1 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $syndic = Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $user->id, 'lot_surface' => 100, 'surface_confirmation' => 100]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2026, 'is_valid' => true, 'fixed_charges_total' => 1000, 'variable_charges_total' => 200]);
        $meeting = Meeting::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'title' => 'Assemblée', 'meeting_date' => now(), 'agenda' => 'Agenda']);
        $vote = Vote::create(['meeting_id' => $meeting->id, 'question' => 'Question', 'status' => 'closed', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $choice = VoteChoice::create(['vote_id' => $vote->id, 'label' => 'Oui']);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 100, 'quote_part_terrain' => 0, 'montant_annuel' => 1200, 'montant_mensuel' => 100, 'charges_surplus' => 0, 'status' => 'a_jour']);
        Invoice::create(['contribution_id' => $contribution->id, 'owner_id' => $owner->id, 'property_id' => $property->id, 'invoice_number' => 'INV-001', 'issue_date' => now()->subDay(), 'due_date' => now()->addDay(), 'amount' => 10, 'status' => 'issued']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Factures impayées');
        $response->assertSee('INV-001');
        $response->assertSee('Décision du dernier vote');
        $response->assertSee('Oui');
    }

    public function test_syndic_dashboard_unblocks_module_cards_after_import_confirmation(): void
    {
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble Import', 'address' => '8 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $property->imported_at = now();
        $property->save();

        $response = $this->actingAs($syndicUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Bientôt disponible');
        $response->assertSee('Cotisations');
        $response->assertSee('Dépenses');
        $response->assertSee('Réclamations');
        $response->assertSee('Réunions');
        $response->assertSee('Messagerie');
        $response->assertSee('Prestataires');
        $response->assertSee('Comptes bureaux');
    }

    public function test_owner_sidebar_exposes_personal_contribution_link_when_contribution_exists(): void
    {
        $ownerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble Sidebar', 'address' => '9 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $ownerUser->id, 'lot_surface' => 100, 'surface_confirmation' => 100]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndicUser->syndic->id, 'year' => 2026, 'is_valid' => true, 'fixed_charges_total' => 1000, 'variable_charges_total' => 200]);
        Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 100, 'quote_part_terrain' => 0, 'montant_annuel' => 1200, 'montant_mensuel' => 100, 'charges_surplus' => 0, 'status' => 'a_jour']);

        $response = $this->actingAs($ownerUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('contributions.owner'));
        $response->assertSee('Ma cotisation');
    }

    public function test_owner_can_view_contribution_and_invoice_history(): void
    {
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble B', 'address' => '2 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $syndic = Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $user->id, 'lot_surface' => 100, 'surface_confirmation' => 100]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2026, 'is_valid' => true, 'fixed_charges_total' => 1000, 'variable_charges_total' => 200]);
        $contribution = Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 100, 'quote_part_terrain' => 0, 'montant_annuel' => 1200, 'montant_mensuel' => 100, 'charges_surplus' => 0, 'status' => 'a_jour']);
        Invoice::create(['contribution_id' => $contribution->id, 'owner_id' => $owner->id, 'property_id' => $property->id, 'invoice_number' => 'INV-PAID', 'issue_date' => now()->subDay(), 'due_date' => now()->addDay(), 'amount' => 15, 'status' => 'paid', 'payment_date' => now()]);
        Invoice::create(['contribution_id' => $contribution->id, 'owner_id' => $owner->id, 'property_id' => $property->id, 'invoice_number' => 'INV-OPEN', 'issue_date' => now()->subDay(), 'due_date' => now()->addDay(), 'amount' => 20, 'status' => 'issued']);

        $response = $this->actingAs($user)->get('/my-contribution');

        $response->assertStatus(200);
        $response->assertSee('Ma cotisation');
        $response->assertSee('INV-OPEN');
        $response->assertSee('INV-PAID');
        $response->assertSee('1 200,00');
    }

    public function test_complaint_statuses_use_the_exact_expected_values(): void
    {
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble C', 'address' => '3 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $user->id, 'lot_surface' => 100, 'surface_confirmation' => 100]);

        $createResponse = $this->actingAs($user)->post('/complaints', [
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'motif' => 'Fuite',
            'description' => 'Description',
            'date' => now()->toDateString(),
            'category' => 'eau',
            'priority' => 'normale',
        ]);

        $createResponse->assertStatus(302);
        $complaint = Complaint::where('subject', 'Fuite')->latest()->first();
        $this->assertNotNull($complaint);
        $this->assertSame('nouvelle', $complaint->status);

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $updateResponse = $this->actingAs($syndicUser)->post('/complaints/' . $complaint->id . '/status', ['status' => 'en_cours']);
        $updateResponse->assertStatus(302);
        $complaint->refresh();
        $this->assertSame('en_cours', $complaint->status);
    }

    public function test_tenant_expiry_command_marks_owner_as_proprietaire_and_sends_password_reset_mail(): void
    {
        Mail::fake();
        $property = Property::create(['name' => 'Immeuble D', 'address' => '4 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $ownerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now(), 'email' => 'owner@example.test']);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $ownerUser->id, 'lot_surface' => 100, 'surface_confirmation' => 100, 'status' => 'locataire']);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => now()->subYear()->toDateString(), 'contract_end_date' => now()->subDay()->toDateString(), 'is_active' => true]);

        $this->artisan('tenants:send-lease-ending-alerts')->assertExitCode(0);

        $owner->refresh();
        $tenant->refresh();
        $this->assertSame('proprietaire', $owner->status);
        $this->assertFalse($tenant->is_active);
        Mail::assertSent(PasswordResetMail::class);
    }

    public function test_owner_cannot_message_another_owner_directly(): void
    {
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble E', 'address' => '5 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $firstOwnerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $secondOwnerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $firstOwner = Owner::create(['property_id' => $property->id, 'user_id' => $firstOwnerUser->id, 'lot_surface' => 60, 'surface_confirmation' => 60]);
        $secondOwner = Owner::create(['property_id' => $property->id, 'user_id' => $secondOwnerUser->id, 'lot_surface' => 40, 'surface_confirmation' => 40]);

        $response = $this->actingAs($firstOwnerUser)->post('/messages', [
            'owner_id' => $secondOwner->id,
            'subject' => 'Direct',
            'body' => 'Nope',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('messages', ['subject' => 'Direct']);
    }

    public function test_owner_sidebar_exposes_contribution_link_when_contribution_exists(): void
    {
        $ownerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble H', 'address' => '10 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $ownerUser->id, 'lot_surface' => 100, 'surface_confirmation' => 100]);
        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndicUser->syndic->id, 'year' => 2026, 'is_valid' => true, 'fixed_charges_total' => 1000, 'variable_charges_total' => 200]);
        Contribution::create(['owner_id' => $owner->id, 'budget_id' => $budget->id, 'tantieme' => 100, 'quote_part_terrain' => 0, 'montant_annuel' => 1200, 'montant_mensuel' => 100, 'charges_surplus' => 0, 'status' => 'a_jour']);

        $response = $this->actingAs($ownerUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('contributions.owner'));
        $response->assertSee('Ma cotisation');
    }

    public function test_syndic_can_reset_owner_password_without_exposing_it_in_response(): void
    {
        Mail::fake();
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble F', 'address' => '6 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);
        $ownerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now(), 'email' => 'reset@example.test']);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $ownerUser->id, 'lot_surface' => 60, 'surface_confirmation' => 60]);

        $response = $this->actingAs($syndicUser)->post('/owners/' . $owner->id . '/reset-password', ['new_password' => 'Secret123!', 'channel' => 'email']);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertFalse(str_contains($response->getContent(), 'Secret123!'));
        $ownerUser->refresh();
        $this->assertTrue(Hash::check('Secret123!', $ownerUser->password));
        Mail::assertSent(PasswordResetMail::class);
    }

    public function test_syndic_calculation_exposes_only_the_connected_owner_contribution(): void
    {
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble G', 'address' => '7 Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $syndic = Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $firstOwnerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $secondOwnerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $firstOwner = Owner::create(['property_id' => $property->id, 'user_id' => $firstOwnerUser->id, 'lot_surface' => 60, 'surface_confirmation' => 60]);
        $secondOwner = Owner::create(['property_id' => $property->id, 'user_id' => $secondOwnerUser->id, 'lot_surface' => 40, 'surface_confirmation' => 40]);

        $budget = Budget::create(['property_id' => $property->id, 'syndic_id' => $syndic->id, 'year' => 2026, 'is_valid' => true, 'fixed_charges_total' => 8000, 'variable_charges_total' => 2000]);

        $this->actingAs($syndicUser)->post('/contributions/calculate', ['property_id' => $property->id]);

        $firstContribution = Contribution::where('owner_id', $firstOwner->id)->where('budget_id', $budget->id)->first();
        $secondContribution = Contribution::where('owner_id', $secondOwner->id)->where('budget_id', $budget->id)->first();

        $this->assertNotNull($firstContribution);
        $this->assertNotNull($secondContribution);

        $this->assertEqualsWithDelta(6000.0, (float) $firstContribution->montant_annuel, 0.01);
        $this->assertEqualsWithDelta(4000.0, (float) $secondContribution->montant_annuel, 0.01);
        $this->assertEqualsWithDelta(500.0, (float) $firstContribution->montant_mensuel, 0.01);
        $this->assertEqualsWithDelta(333.33, (float) $secondContribution->montant_mensuel, 0.01);

        $response = $this->actingAs($firstOwnerUser)->get('/my-contribution');

        $response->assertStatus(200);
        $response->assertSee('600,00 %');
        $response->assertSee('6 000,00');
        $response->assertDontSee('4 000,00');
    }
}
