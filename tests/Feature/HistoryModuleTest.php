<?php

namespace Tests\Feature;

use App\Models\Activity as ActivityModel;
use App\Models\Budget;
use App\Models\Complaint;
use App\Models\Meeting;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteChoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_syndic_can_view_activity_history_for_own_property_with_filters(): void
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create(['name' => 'Immeuble Histo', 'address' => '1 Rue Histoire']);
        $syndic = Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);

        $otherUser = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->withProperties(['action_type' => 'login', 'property_id' => $property->id])
            ->log('User logged in');

        activity()
            ->causedBy($otherUser)
            ->withProperties(['action_type' => 'budget.create', 'property_id' => $property->id])
            ->log('Budget created');

        $response = $this->actingAs($user)->get(route('history.index', ['type' => 'budget.create', 'user_id' => $otherUser->id]));

        $response->assertStatus(200);
        $response->assertSee('Budget created');
        $response->assertDontSee('User logged in');
    }

    public function test_syndic_history_is_limited_to_own_property_and_non_syndic_cannot_access(): void
    {
        $user = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $otherSyndic = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
        ]);

        $property = Property::create(['name' => 'Immeuble Histo', 'address' => '1 Rue Histoire']);
        $otherProperty = Property::create(['name' => 'Immeuble Autre', 'address' => '2 Rue Autre']);

        Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        Syndic::create(['user_id' => $otherSyndic->id, 'property_id' => $otherProperty->id]);

        activity()
            ->causedBy($user)
            ->withProperties(['action_type' => 'login', 'property_id' => $property->id])
            ->log('Own property activity');

        activity()
            ->causedBy($otherSyndic)
            ->withProperties(['action_type' => 'login', 'property_id' => $otherProperty->id])
            ->log('Other property activity');

        $ownerUser = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('history.index'));
        $response->assertStatus(200);
        $response->assertSee('Own property activity');
        $response->assertDontSee('Other property activity');

        $response = $this->actingAs($ownerUser)->get(route('history.index'));
        $response->assertStatus(403);
    }

    public function test_activity_log_entries_are_created_for_login_budget_vote_and_complaint_actions(): void
    {
        $syndicUser = User::factory()->create([
            'role' => 'syndic',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $property = Property::create(['name' => 'Immeuble Action', 'address' => '3 Rue Action']);
        $syndic = Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $ownerUser = User::factory()->create([
            'role' => 'copropriétaire',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $owner = Owner::create([
            'property_id' => $property->id,
            'user_id' => $ownerUser->id,
            'status' => 'proprietaire',
            'lot_surface' => 50,
            'surface_confirmation' => 50,
        ]);

        $meeting = Meeting::create([
            'property_id' => $property->id,
            'syndic_id' => $syndic->id,
            'title' => 'Assemblée test',
            'meeting_date' => now()->addDays(1),
            'agenda' => 'Ordre du jour',
        ]);

        $vote = Vote::create([
            'meeting_id' => $meeting->id,
            'question' => 'Vote test',
            'status' => 'open',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $choice = VoteChoice::create(['vote_id' => $vote->id, 'label' => 'Oui']);

        $loginResponse = $this->post('/login', [
            'email' => $syndicUser->email,
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect('/dashboard');
        $this->assertDatabaseHas('activity_log', [
            'description' => 'User logged in',
            'causer_id' => $syndicUser->id,
        ]);

        $budgetResponse = $this->actingAs($syndicUser)->post(route('budgets.store'), [
            'year' => 2026,
            'fixed_charges_total' => 1000,
            'variable_charges_total' => 500,
        ]);

        $budgetResponse->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('activity_log', [
            'description' => 'Budget created',
            'causer_id' => $syndicUser->id,
        ]);

        $participationResponse = $this->actingAs($ownerUser)->post(route('votes.participate', $vote), [
            'vote_choice_id' => $choice->id,
        ]);

        $participationResponse->assertRedirect();
        $this->assertDatabaseHas('activity_log', [
            'description' => 'Vote participation created',
            'causer_id' => $ownerUser->id,
        ]);

        $complaintResponse = $this->actingAs($ownerUser)->post(route('complaints.store'), [
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'subject' => 'Problème test',
            'description' => 'Description de test',
        ]);

        $complaintResponse->assertRedirect(route('complaints.index'));
        $this->assertDatabaseHas('activity_log', [
            'description' => 'Complaint created',
            'causer_id' => $ownerUser->id,
        ]);
    }
}
