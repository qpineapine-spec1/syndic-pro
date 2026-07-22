<?php

namespace Tests\Feature;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Models\MeetingRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingRequestModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwnerContext(): array
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Prop MR', 'address' => '1 MR']);
        $owner = Owner::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
        ]);

        return [$user, $owner, $property];
    }

    private function makeOwnerContextForProperty(Property $property): array
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner = Owner::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'lot_surface' => 50,
            'surface_confirmation' => 50,
        ]);

        return [$user, $owner];
    }

    public function test_owner_can_create_meeting_request_for_own_property(): void
    {
        [$user, $owner, $property] = $this->makeOwnerContext();

        $response = $this->actingAs($user)->post('/meeting-requests', [
            'title' => 'Demande AG',
            'motif' => 'Important',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('meeting_requests', [
            'title' => 'Demande AG',
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_cannot_see_meeting_requests_of_other_property(): void
    {
        [$userA, $ownerA, $propertyA] = $this->makeOwnerContext();
        [$userB, $ownerB] = $this->makeOwnerContextForProperty(Property::create(['name' => 'Prop B', 'address' => '2 B']));

        MeetingRequest::create([
            'title' => 'Req B',
            'description' => '...',
            'property_id' => $ownerB->property_id,
            'owner_id' => $ownerB->id,
            'required_threshold' => 1,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($userA)->get('/meeting-requests');
        $response->assertStatus(200);
        $response->assertViewHas('requests', function ($requests) use ($propertyA) {
            foreach ($requests as $request) {
                if ($request->property_id !== $propertyA->id) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_syndic_can_see_all_meeting_requests_for_own_property_only(): void
    {
        [$ownerUser, $owner, $property] = $this->makeOwnerContext();
        /** @var User $syndicUser */
        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $syndic = Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        MeetingRequest::create([
            'title' => 'Req 1',
            'description' => 'A',
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'required_threshold' => 1,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $otherProperty = Property::create(['name' => 'Autre', 'address' => 'Autre']);
        MeetingRequest::create([
            'title' => 'Req 2',
            'description' => 'B',
            'property_id' => $otherProperty->id,
            'owner_id' => $owner->id,
            'required_threshold' => 1,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($syndicUser)->get('/meeting-requests');
        $response->assertStatus(200);
        $response->assertViewHas('requests', function ($requests) use ($property) {
            return $requests->every(fn ($request) => $request->property_id === $property->id);
        });
    }

    public function test_owner_can_vote_for_meeting_request_once(): void
    {
        [$user, $owner, $property] = $this->makeOwnerContext();
        $requiredThreshold = (int) ceil(Owner::where('property_id', $property->id)->count() / 3);
        $request = MeetingRequest::create([
            'title' => 'TR',
            'description' => 'X',
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'required_threshold' => $requiredThreshold,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $firstResponse = $this->actingAs($user)->post('/meeting-requests/' . $request->id . '/vote');
        $firstResponse->assertStatus(302);
        $this->assertDatabaseHas('meeting_request_votes', ['meeting_request_id' => $request->id, 'owner_id' => $owner->id]);

        $secondResponse = $this->actingAs($user)->post('/meeting-requests/' . $request->id . '/vote');
        $secondResponse->assertStatus(403);
    }

    public function test_meeting_request_triggers_automatically_at_one_third(): void
    {
        [$user, $owner, $property] = $this->makeOwnerContext();
        $this->makeOwnerContextForProperty($property);
        $this->makeOwnerContextForProperty($property);

        $requiredThreshold = (int) ceil(Owner::where('property_id', $property->id)->count() / 3);
        $request = MeetingRequest::create([
            'title' => 'Seuil',
            'description' => 'Z',
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'required_threshold' => $requiredThreshold,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post('/meeting-requests/' . $request->id . '/vote');

        $request->refresh();
        $this->assertSame('triggered', $request->status);
        $this->assertNotNull($request->meeting_id);
        $this->assertNotNull($request->triggered_at);
        $this->assertDatabaseCount('meetings', 1);
    }

    public function test_meeting_request_does_not_trigger_below_threshold(): void
    {
        [$user, $owner, $property] = $this->makeOwnerContext();
        $this->makeOwnerContextForProperty($property);
        $this->makeOwnerContextForProperty($property);
        $this->makeOwnerContextForProperty($property);

        $requiredThreshold = (int) ceil(Owner::where('property_id', $property->id)->count() / 3);
        $request = MeetingRequest::create([
            'title' => 'NoTrigger',
            'description' => 'N',
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'required_threshold' => $requiredThreshold,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post('/meeting-requests/' . $request->id . '/vote');

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->meeting_id);
        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_owner_cannot_vote_on_other_property_meeting_request(): void
    {
        [$userA, $ownerA, $propertyA] = $this->makeOwnerContext();
        [$userB, $ownerB, $propertyB] = $this->makeOwnerContext();

        $requiredThreshold = (int) ceil(Owner::where('property_id', $propertyB->id)->count() / 3);
        $request = MeetingRequest::create([
            'title' => 'RB',
            'description' => 'Y',
            'property_id' => $propertyB->id,
            'owner_id' => $ownerB->id,
            'required_threshold' => $requiredThreshold,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($userA)->post('/meeting-requests/' . $request->id . '/vote');
        $response->assertStatus(403);
    }
}
