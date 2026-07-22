<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MeetingVoteModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Immeuble Réunion', 'address' => '1 Rue Test']);
        Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        return [$user, $property];
    }

    private function makeOwnerContextForProperty(Property $property, $withUser = true)
    {
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $user->id, 'lot_surface' => 50, 'surface_confirmation' => 50]);
        return [$user, $owner];
    }

    public function test_syndic_can_create_meeting_for_own_property(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();

        $response = $this->actingAs($syndicUser)->post('/meetings', [
            'title' => 'Assemblée Générale',
            'meeting_date' => now()->addWeeks(2)->toDateTimeString(),
            'agenda' => 'Validation budget',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('meetings', ['title' => 'Assemblée Générale', 'property_id' => $property->id]);
    }

    public function test_owner_cannot_create_meeting(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);

        $response = $this->actingAs($ownerUser)->post('/meetings', [
            'title' => 'Réunion non autorisée',
            'meeting_date' => now()->addWeeks(1)->toDateTimeString(),
            'agenda' => 'Sujet',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_meeting_creation_notifies_all_owners_of_property(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();

        // create 3 owners for this property
        $owners = [];
        for ($i = 0; $i < 3; $i++) {
            [$u, $o] = $this->makeOwnerContextForProperty($property);
            $owners[] = $u;
        }

        $response = $this->actingAs($syndicUser)->post('/meetings', [
            'title' => 'Réunion Notif',
            'meeting_date' => now()->addDays(7)->toDateTimeString(),
            'agenda' => 'Ordre du jour',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(302);

        // BR-14: expect a record in the app notifications table for each owner
        foreach ($owners as $u) {
            $this->assertDatabaseHas('notifications', [
                'owner_id' => $u->owner->id,
                'property_id' => $property->id,
                'channel' => 'interne',
                'title' => 'Nouvelle réunion: Réunion Notif',
            ]);
        }
    }

    public function test_owner_cannot_see_meetings_of_other_property(): void
    {
        // property A and owner A
        $propertyA = Property::create(['name' => 'A', 'address' => 'A']);
        [$userA, $ownerA] = $this->makeOwnerContextForProperty($propertyA);

        // property B and meeting B
        $propertyB = Property::create(['name' => 'B', 'address' => 'B']);
        $userS = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $userS->id, 'property_id' => $propertyB->id]);
        $meetingB = Meeting::create(['title' => 'Réunion B', 'meeting_date' => now()->addDays(3), 'agenda' => '...', 'property_id' => $propertyB->id, 'syndic_id' => $userS->syndic->id ?? null, 'status' => 'scheduled']);

        $response = $this->actingAs($userA)->get('/meetings');
        $response->assertStatus(200);
        $response->assertViewHas('meetings', function ($meetings) use ($propertyA) {
            foreach ($meetings as $m) {
                if ($m->property_id !== $propertyA->id) return false;
            }
            return true;
        });
    }

    public function test_syndic_can_open_and_close_vote_for_own_meeting(): void
    {
        [$syndicUser, $property] = $this->makeSyndicContext();
        $meeting = Meeting::create(['title' => 'Vote Meeting', 'meeting_date' => now()->addDays(5), 'agenda' => 'Vote', 'property_id' => $property->id, 'syndic_id' => $syndicUser->syndic->id ?? null, 'status' => 'scheduled']);

        $response = $this->actingAs($syndicUser)->post('/votes', [
            'meeting_id' => $meeting->id,
            'question' => 'Approuvez-vous?',
            'choices' => ['Oui', 'Non'],
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('votes', ['meeting_id' => $meeting->id, 'question' => 'Approuvez-vous?', 'status' => 'open']);

        $vote = \App\Models\Vote::first();
        $close = $this->actingAs($syndicUser)->post('/votes/' . $vote->id . '/close');
        $close->assertStatus(302);
        $vote->refresh();
        $this->assertEquals('closed', $vote->status);
    }

    public function test_syndic_cannot_manage_vote_of_other_property_meeting(): void
    {
        // syndic 1 and property 1
        [$syndic1, $property1] = $this->makeSyndicContext();
        // syndic 2 and property 2
        $user2 = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property2 = Property::create(['name' => 'Prop2', 'address' => 'Addr2']);
        Syndic::create(['user_id' => $user2->id, 'property_id' => $property2->id]);

        $meeting2 = Meeting::create(['title' => 'M2', 'meeting_date' => now()->addDays(4), 'agenda' => '...', 'property_id' => $property2->id, 'syndic_id' => $user2->syndic->id ?? null, 'status' => 'scheduled']);

        $response = $this->actingAs($syndic1)->post('/votes', [
            'meeting_id' => $meeting2->id,
            'question' => 'Q',
            'choices' => ['Oui', 'Non'],
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
        ]);
        $response->assertStatus(403);
    }

    public function test_owner_can_participate_once_per_vote(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);

        $meeting = Meeting::create(['title' => 'M', 'meeting_date' => now()->addDays(2), 'agenda' => '...', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);

        $vote = \App\Models\Vote::create(['meeting_id' => $meeting->id, 'question' => 'Q', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'status' => 'open']);
        $choice = \App\Models\VoteChoice::create(['vote_id' => $vote->id, 'label' => 'Oui']);

        $resp1 = $this->actingAs($ownerUser)->post('/votes/' . $vote->id . '/participate', ['vote_choice_id' => $choice->id]);
        $resp1->assertStatus(302);

        $resp2 = $this->actingAs($ownerUser)->post('/votes/' . $vote->id . '/participate', ['vote_choice_id' => $choice->id]);
        $resp2->assertStatus(403);
    }

    public function test_owner_can_vote_up_to_the_configured_limit_for_multiple_choice_votes(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);

        $meeting = Meeting::create(['title' => 'Multiple vote', 'meeting_date' => now()->addDays(2), 'agenda' => '...', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);

        $singleResponse = $this->actingAs($syndic)->post('/votes', [
            'meeting_id' => $meeting->id,
            'question' => 'Choix unique',
            'choices' => ['Oui'],
            'vote_type' => 'single',
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
        ]);
        $singleResponse->assertStatus(302);
        $singleVote = \App\Models\Vote::where('meeting_id', $meeting->id)->where('question', 'Choix unique')->latest()->first();
        $singleChoice = \App\Models\VoteChoice::where('vote_id', $singleVote->id)->first();

        $singleResp1 = $this->actingAs($ownerUser)->post('/votes/' . $singleVote->id . '/participate', ['vote_choice_ids' => [$singleChoice->id]]);
        $singleResp1->assertStatus(302);

        $singleResp2 = $this->actingAs($ownerUser)->post('/votes/' . $singleVote->id . '/participate', ['vote_choice_ids' => [$singleChoice->id]]);
        $singleResp2->assertStatus(403);

        $multiResponse = $this->actingAs($syndic)->post('/votes', [
            'meeting_id' => $meeting->id,
            'question' => 'Choix multiple',
            'choices' => ['Oui', 'Non', 'Abstention'],
            'vote_type' => 'multiple',
            'nb_choix_autorises' => 2,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
        ]);
        $multiResponse->assertStatus(302);
        $multiVote = \App\Models\Vote::where('meeting_id', $meeting->id)->where('question', 'Choix multiple')->latest()->first();
        $choiceA = \App\Models\VoteChoice::where('vote_id', $multiVote->id)->where('label', 'Oui')->first();
        $choiceB = \App\Models\VoteChoice::where('vote_id', $multiVote->id)->where('label', 'Non')->first();
        $choiceC = \App\Models\VoteChoice::where('vote_id', $multiVote->id)->where('label', 'Abstention')->first();

        $multiResp1 = $this->actingAs($ownerUser)->post('/votes/' . $multiVote->id . '/participate', ['vote_choice_ids' => [$choiceA->id]]);
        $multiResp1->assertStatus(302);

        $multiResp2 = $this->actingAs($ownerUser)->post('/votes/' . $multiVote->id . '/participate', ['vote_choice_ids' => [$choiceB->id]]);
        $multiResp2->assertStatus(302);

        $multiResp3 = $this->actingAs($ownerUser)->post('/votes/' . $multiVote->id . '/participate', ['vote_choice_ids' => [$choiceC->id]]);
        $multiResp3->assertStatus(403);
    }

    public function test_owner_cannot_vote_on_other_property_meeting(): void
    {
        // property A with vote
        $propertyA = Property::create(['name' => 'A', 'address' => 'A']);
        $userSA = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $userSA->id, 'property_id' => $propertyA->id]);
        $meetingA = Meeting::create(['title' => 'MA', 'meeting_date' => now()->addDay(), 'agenda' => '...', 'property_id' => $propertyA->id, 'syndic_id' => $userSA->syndic->id ?? null, 'status' => 'scheduled']);
        $vote = \App\Models\Vote::create(['meeting_id' => $meetingA->id, 'question' => 'Q', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'status' => 'open']);
        $choice = \App\Models\VoteChoice::create(['vote_id' => $vote->id, 'label' => 'Oui']);

        // owner from other property B
        $propertyB = Property::create(['name' => 'B', 'address' => 'B']);
        [$userB, $ownerB] = $this->makeOwnerContextForProperty($propertyB);

        $resp = $this->actingAs($userB)->post('/votes/' . $vote->id . '/participate', ['vote_choice_id' => $choice->id]);
        $resp->assertStatus(403);
    }

    public function test_vote_results_reflect_participation_counts(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        // create 3 owners
        $users = [];
        for ($i = 0; $i < 3; $i++) {
            [$u, $o] = $this->makeOwnerContextForProperty($property);
            $users[] = $u;
        }

        $meeting = Meeting::create(['title' => 'Résultats', 'meeting_date' => now()->addDays(3), 'agenda' => '...', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);
        $vote = \App\Models\Vote::create(['meeting_id' => $meeting->id, 'question' => 'Q', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'status' => 'open']);
        $choice1 = \App\Models\VoteChoice::create(['vote_id' => $vote->id, 'label' => 'Oui']);
        $choice2 = \App\Models\VoteChoice::create(['vote_id' => $vote->id, 'label' => 'Non']);

        // user0 -> choice1, user1 -> choice2, user2 -> choice1
        $this->actingAs($users[0])->post('/votes/' . $vote->id . '/participate', ['vote_choice_id' => $choice1->id]);
        $this->actingAs($users[1])->post('/votes/' . $vote->id . '/participate', ['vote_choice_id' => $choice2->id]);
        $this->actingAs($users[2])->post('/votes/' . $vote->id . '/participate', ['vote_choice_id' => $choice1->id]);

        $this->assertDatabaseCount('vote_participations', 3);

        // check counts per choice
        $countYes = \App\Models\VoteParticipation::where('vote_choice_id', $choice1->id)->count();
        $countNo = \App\Models\VoteParticipation::where('vote_choice_id', $choice2->id)->count();

        $this->assertEquals(2, $countYes);
        $this->assertEquals(1, $countNo);
    }

    public function test_vote_reminder_sent_2h_before_closing(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        // create 3 owners
        $owners = [];
        for ($i = 0; $i < 3; $i++) {
            [$u, $o] = $this->makeOwnerContextForProperty($property);
            $owners[] = $o;
        }

        $meeting = Meeting::create(['title' => 'Rappel', 'meeting_date' => now()->addDays(1), 'agenda' => '...', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);
        $vote = \App\Models\Vote::create(['meeting_id' => $meeting->id, 'question' => 'Q rappel', 'starts_at' => now()->subHours(1), 'ends_at' => now()->addHour(), 'status' => 'open']);

        // Ensure no participations exist
        $this->assertDatabaseCount('vote_participations', 0);

        $this->artisan('vote:send-closing-reminders')->assertExitCode(0);

        // notifications created for each owner without participation
        foreach ($owners as $o) {
            $this->assertDatabaseHas('notifications', [
                'owner_id' => $o->id,
                'property_id' => $property->id,
                'channel' => 'interne',
            ]);
        }

        $vote->refresh();
        $this->assertNotNull($vote->reminder_sent_at);

        // second run should not duplicate notifications (reminder_sent_at prevents re-send)
        $countBefore = \App\Models\Notification::where('property_id', $property->id)->count();
        $this->artisan('vote:send-closing-reminders')->assertExitCode(0);
        $countAfter = \App\Models\Notification::where('property_id', $property->id)->count();
        $this->assertEquals($countBefore, $countAfter);
    }

    public function test_vote_reminder_not_sent_before_2h_window(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$u, $o] = $this->makeOwnerContextForProperty($property);

        $meeting = Meeting::create(['title' => 'Pas encore', 'meeting_date' => now()->addDays(2), 'agenda' => '...', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);
        $vote = \App\Models\Vote::create(['meeting_id' => $meeting->id, 'question' => 'Q no', 'starts_at' => now(), 'ends_at' => now()->addHours(3), 'status' => 'open']);

        $this->artisan('vote:send-closing-reminders')->assertExitCode(0);

        $this->assertDatabaseMissing('notifications', ['property_id' => $property->id, 'channel' => 'interne', 'title' => 'Rappel de clôture de vote: ' . $vote->question]);
        $vote->refresh();
        $this->assertNull($vote->reminder_sent_at);
    }

    public function test_syndic_can_generate_and_download_meeting_report_template(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();

        $meeting = Meeting::create(['title' => 'Template Meeting', 'meeting_date' => now()->addDays(2), 'agenda' => 'Agenda X', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);

        $resp = $this->actingAs($syndic)->get(route('meetings.report.template', $meeting));
        $resp->assertStatus(200);
        $resp->assertHeader('content-disposition');
    }

    public function test_syndic_cannot_generate_template_for_other_property_meeting(): void
    {
        [$syndic1, $property1] = $this->makeSyndicContext();
        $user2 = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property2 = Property::create(['name' => 'Other', 'address' => 'X']);
        Syndic::create(['user_id' => $user2->id, 'property_id' => $property2->id]);

        $meeting2 = Meeting::create(['title' => 'Other Meeting', 'meeting_date' => now()->addDays(3), 'agenda' => 'A', 'property_id' => $property2->id, 'syndic_id' => $user2->syndic->id ?? null, 'status' => 'scheduled']);

        $resp = $this->actingAs($syndic1)->get(route('meetings.report.template', $meeting2));
        $resp->assertStatus(403);
    }

    public function test_syndic_can_upload_meeting_report_and_anti_idor_and_owner_forbidden(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);

        $meeting = Meeting::create(['title' => 'Upload Meeting', 'meeting_date' => now()->addDays(2), 'agenda' => 'Agenda', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);

        \Illuminate\Support\Facades\Storage::fake('public');
        $file = \Illuminate\Http\UploadedFile::fake()->create('report.docx', 100);

        $resp = $this->actingAs($ownerUser)->post(route('meetings.report.upload', $meeting), ['report' => $file]);
        $resp->assertStatus(403);

        $resp2 = $this->actingAs($syndic)->post(route('meetings.report.upload', $meeting), ['report' => $file]);
        $resp2->assertStatus(302);

        $meeting->refresh();
        $this->assertNotNull($meeting->compte_rendu);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($meeting->compte_rendu);

        // another syndic cannot upload to this meeting
        $other = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $otherProp = Property::create(['name' => 'Other P', 'address' => 'Addr']);
        Syndic::create(['user_id' => $other->id, 'property_id' => $otherProp->id]);

        $resp3 = $this->actingAs($other)->post(route('meetings.report.upload', $meeting), ['report' => $file]);
        $resp3->assertStatus(403);
    }

    public function test_owner_cannot_upload_meeting_report(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);

        $meeting = Meeting::create(['title' => 'Owner Upload Meeting', 'meeting_date' => now()->addDays(2), 'agenda' => 'Agenda', 'property_id' => $property->id, 'syndic_id' => $syndic->syndic->id ?? null, 'status' => 'scheduled']);

        \Illuminate\Support\Facades\Storage::fake('public');
        $file = \Illuminate\Http\UploadedFile::fake()->create('report.docx', 100);

        $resp = $this->actingAs($ownerUser)->post(route('meetings.report.upload', $meeting), ['report' => $file]);
        $resp->assertStatus(403);
    }
}
