<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteChoice;
use App\Models\VoteParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingWorkflowRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble Workflow', 'address' => '10 Rue Test']);
        Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);

        return [$user, $property];
    }

    private function makeOwnerContextForProperty(Property $property): array
    {
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $user->id, 'lot_surface' => 50, 'surface_confirmation' => 50]);

        return [$user, $owner];
    }

    public function test_syndic_can_create_meeting_and_notify_owners_when_requested(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();
        [$firstOwnerUser, $firstOwner] = $this->makeOwnerContextForProperty($property);
        [$secondOwnerUser, $secondOwner] = $this->makeOwnerContextForProperty($property);

        $response = $this->actingAs($syndicUser)->post('/meetings', [
            'title' => 'Assemblée Générale',
            'type_reunion' => 'assemblee_generale',
            'meeting_date' => now()->addDays(2)->toDateTimeString(),
            'agenda' => 'Ordre du jour',
            'lieu' => 'Salle des fêtes',
            'notify_owners' => true,
            'property_id' => $property->id,
        ]);

        $response->assertStatus(302);
        $meeting = Meeting::where('title', 'Assemblée Générale')->latest()->first();
        $this->assertNotNull($meeting);
        $this->assertSame('assemblee_generale', $meeting->type_reunion);
        $this->assertSame('Salle des fêtes', $meeting->lieu);
        $this->assertDatabaseHas('notifications', ['property_id' => $property->id, 'owner_id' => $firstOwner->id, 'channel' => 'interne']);
        $this->assertDatabaseHas('notifications', ['property_id' => $property->id, 'owner_id' => $secondOwner->id, 'channel' => 'interne']);
    }

    public function test_syndic_update_meeting_notifies_owners_automatically(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);
        $meeting = Meeting::create([
            'property_id' => $property->id,
            'syndic_id' => $syndicUser->syndic->id,
            'title' => 'Réunion initiale',
            'meeting_date' => now()->addDays(5),
            'agenda' => 'Agenda',
            'lieu' => 'Local',
            'type_reunion' => 'reunion_conseil',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($syndicUser)->put('/meetings/' . $meeting->id, [
            'title' => 'Réunion modifiée',
            'type_reunion' => 'reunion_extraordinaire',
            'meeting_date' => now()->addDays(6)->toDateTimeString(),
            'agenda' => 'Nouveau programme',
            'lieu' => 'Salle de réunion',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(302);
        $meeting->refresh();
        $this->assertSame('Réunion modifiée', $meeting->title);
        $this->assertDatabaseHas('notifications', ['property_id' => $property->id, 'owner_id' => $owner->id, 'channel' => 'interne', 'title' => 'Réunion modifiée: Réunion modifiée']);
    }

    public function test_syndic_can_create_vote_with_multiple_choices_and_closure_winner_is_displayed(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);
        $meeting = Meeting::create([
            'property_id' => $property->id,
            'syndic_id' => $syndicUser->syndic->id,
            'title' => 'Vote test',
            'meeting_date' => now()->addDays(1),
            'agenda' => 'Agenda',
            'lieu' => 'Local',
            'type_reunion' => 'assemblee_generale',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($syndicUser)->post('/votes', [
            'meeting_id' => $meeting->id,
            'question' => 'Approuvez-vous ce projet ?',
            'choices' => ['Oui', 'Non', 'Abstention'],
            'nb_choix_autorises' => 2,
            'starts_at' => now()->subMinute()->toDateTimeString(),
            'ends_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertStatus(302);
        $vote = Vote::where('meeting_id', $meeting->id)->latest()->first();
        $this->assertNotNull($vote);
        $this->assertSame(2, $vote->nb_choix_autorises);
        $this->assertDatabaseHas('vote_choices', ['vote_id' => $vote->id, 'label' => 'Oui']);
        $this->assertDatabaseHas('vote_choices', ['vote_id' => $vote->id, 'label' => 'Non']);
        $this->assertDatabaseHas('vote_choices', ['vote_id' => $vote->id, 'label' => 'Abstention']);

        $choice = VoteChoice::where('vote_id', $vote->id)->where('label', 'Oui')->first();
        $this->actingAs($ownerUser)->post('/votes/' . $vote->id . '/participate', ['vote_choice_ids' => [$choice->id]]);

        $this->actingAs($syndicUser)->post('/votes/' . $vote->id . '/close');
        $vote->refresh();
        $this->assertSame('closed', $vote->status);
        $this->assertSame('Oui', $vote->final_decision);
    }

    public function test_meeting_request_threshold_creates_meeting_once(): void
    {
        [$ownerUser, $owner, $property] = [null, null, null];
        $ownerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble Demande', 'address' => '11 Rue Test']);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $ownerUser->id, 'lot_surface' => 60, 'surface_confirmation' => 60]);
        $secondOwnerUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        Owner::create(['property_id' => $property->id, 'user_id' => $secondOwnerUser->id, 'lot_surface' => 40, 'surface_confirmation' => 40]);
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        $request = MeetingRequest::create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'title' => 'Demande extraordinaire',
            'description' => 'Motif',
            'required_threshold' => 1,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($ownerUser)->post('/meeting-requests/' . $request->id . '/vote');
        $this->assertDatabaseHas('meetings', ['property_id' => $property->id, 'type_reunion' => 'reunion_extraordinaire']);

        $this->actingAs($secondOwnerUser)->post('/meeting-requests/' . $request->id . '/vote');
        $this->assertDatabaseCount('meetings', 1);
    }
}
