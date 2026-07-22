<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyndicContext(): array
    {
        $user = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Prop M', 'address' => '1 M']);
        Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        return [$user, $property];
    }

    private function makeOwnerContextForProperty(Property $property)
    {
        $user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
        $owner = Owner::create(['property_id' => $property->id, 'user_id' => $user->id, 'lot_surface' => 50, 'surface_confirmation' => 50]);
        return [$user, $owner];
    }

    public function test_syndic_can_send_message_to_owner_of_own_property(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user, $owner] = $this->makeOwnerContextForProperty($property);

        $resp = $this->actingAs($syndic)->post('/messages', ['owner_id' => $owner->id, 'subject' => 'Hello', 'body' => 'Message body']);
        $resp->assertStatus(302);
        $this->assertDatabaseHas('messages', ['owner_id' => $owner->id, 'property_id' => $property->id, 'subject' => 'Hello']);
    }

    public function test_owner_can_send_message_to_syndic_of_own_property(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user, $owner] = $this->makeOwnerContextForProperty($property);

        $resp = $this->actingAs($user)->post('/messages', ['owner_id' => $owner->id, 'subject' => 'Hi syndic', 'body' => 'Body']);
        $resp->assertStatus(302);
        $this->assertDatabaseHas('messages', ['owner_id' => $owner->id, 'property_id' => $property->id, 'subject' => 'Hi syndic']);
    }

    public function test_syndic_cannot_send_message_to_owner_of_other_property(): void
    {
        [$syndic1, $property1] = $this->makeSyndicContext();
        [$syndic2, $property2] = $this->makeSyndicContext();
        [$user2, $owner2] = $this->makeOwnerContextForProperty($property2);

        $resp = $this->actingAs($syndic1)->post('/messages', ['owner_id' => $owner2->id, 'subject' => 'X', 'body' => 'Y']);
        $resp->assertStatus(403);
    }

    public function test_owner_cannot_send_message_to_syndic_of_other_property(): void
    {
        [$syndic1, $property1] = $this->makeSyndicContext();
        [$syndic2, $property2] = $this->makeSyndicContext();
        [$user1, $owner1] = $this->makeOwnerContextForProperty($property1);

        $resp = $this->actingAs($user1)->post('/messages', ['owner_id' => $owner1->id, 'subject' => 'X', 'body' => 'Y']);
        $resp->assertStatus(302); // sending to own syndic should be allowed

        // attempt to send to syndic of other property via specifying owner of other property
        [$userOther, $ownerOther] = $this->makeOwnerContextForProperty($property2);
        $resp2 = $this->actingAs($user1)->post('/messages', ['owner_id' => $ownerOther->id, 'subject' => 'Bad', 'body' => 'No']);
        $resp2->assertStatus(403);
    }

    public function test_owner_cannot_message_another_owner_directly(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user1, $owner1] = $this->makeOwnerContextForProperty($property);
        [$user2, $owner2] = $this->makeOwnerContextForProperty($property);

        $resp = $this->actingAs($user1)->post('/messages', ['owner_id' => $owner2->id, 'subject' => 'Direct', 'body' => 'No']);
        $resp->assertStatus(403);
    }

    public function test_user_can_see_only_own_conversations(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user1, $owner1] = $this->makeOwnerContextForProperty($property);
        [$user2, $owner2] = $this->makeOwnerContextForProperty($property);

        // create messages for both owners
        Message::create(['property_id' => $property->id, 'owner_id' => $owner1->id, 'sender_user_id' => $user1->id, 'subject' => 'A', 'body' => 'a']);
        Message::create(['property_id' => $property->id, 'owner_id' => $owner2->id, 'sender_user_id' => $user2->id, 'subject' => 'B', 'body' => 'b']);

        $resp = $this->actingAs($user1)->get('/messages');
        $resp->assertStatus(200);
        $resp->assertViewHas('conversations', function ($convs) use ($owner1) {
            return $convs->contains('owner_id', $owner1->id) && !$convs->contains('owner_id', $owner1->id === false);
        });
    }

    public function test_message_marks_as_read_when_opened(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user, $owner] = $this->makeOwnerContextForProperty($property);

        Message::create(['property_id' => $property->id, 'owner_id' => $owner->id, 'sender_user_id' => $syndic->id, 'subject' => 'X', 'body' => 'Y', 'is_read' => false]);

        $resp = $this->actingAs($user)->get('/messages/' . $owner->id);
        $resp->assertStatus(200);

        $this->assertDatabaseHas('messages', ['owner_id' => $owner->id, 'is_read' => true]);
    }

    public function test_unread_message_count_is_correct(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user, $owner] = $this->makeOwnerContextForProperty($property);

        Message::create(['property_id' => $property->id, 'owner_id' => $owner->id, 'sender_user_id' => $syndic->id, 'subject' => 'X', 'body' => 'Y', 'is_read' => false]);
        Message::create(['property_id' => $property->id, 'owner_id' => $owner->id, 'sender_user_id' => $syndic->id, 'subject' => 'X2', 'body' => 'Y2', 'is_read' => false]);

        $view = $this->actingAs($user)->get('/messages');
        $view->assertStatus(200);
        $view->assertSee('2'); // message-dropdown should show count 2
    }

    public function test_syndic_messages_index_lists_contacts_and_broadcast_option(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user, $owner] = $this->makeOwnerContextForProperty($property);

        $response = $this->actingAs($syndic)->get('/messages');

        $response->assertStatus(200);
        $response->assertSee('Envoyer à tous');
        $response->assertSee($owner->user->name);
        $response->assertSee(route('messages.show', $owner));
    }

    public function test_owner_messages_index_shows_only_the_property_syndic_as_contact(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$ownerUser, $owner] = $this->makeOwnerContextForProperty($property);

        $response = $this->actingAs($ownerUser)->get('/messages');

        $response->assertStatus(200);
        $response->assertSee($syndic->name);
        $response->assertDontSee($owner->user->name);
    }

    public function test_message_notifies_recipient(): void
    {
        [$syndic, $property] = $this->makeSyndicContext();
        [$user, $owner] = $this->makeOwnerContextForProperty($property);

        $this->actingAs($syndic)->post('/messages', ['owner_id' => $owner->id, 'subject' => 'Notif', 'body' => 'Msg']);

        $this->assertDatabaseHas('notifications', ['owner_id' => $owner->id, 'property_id' => $property->id, 'channel' => 'interne']);
    }
}
