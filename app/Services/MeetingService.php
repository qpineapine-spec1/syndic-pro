<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Owner;
use App\Models\Syndic;

class MeetingService
{
    public function createMeetingForProperty($property, $title, $meeting_date, $agenda, $syndicId, $typeReunion = 'assemblee_generale', $lieu = null, $notifyOwners = true)
    {
        $meeting = Meeting::create([
            'title' => $title,
            'meeting_date' => $meeting_date,
            'agenda' => $agenda,
            'property_id' => $property->id,
            'syndic_id' => $syndicId,
            'type_reunion' => $typeReunion,
            'lieu' => $lieu,
            'status' => 'scheduled',
        ]);

        if ($notifyOwners) {
            $owners = Owner::where('property_id', $property->id)->get();
            $this->notifyOwners(
                $property,
                $owners,
                'Nouvelle réunion: ' . $meeting->title,
                'Réunion prévue le ' . $meeting->meeting_date
            );
        }

        return $meeting;
    }

    /**
     * Create an internal notification for each owner given, targeting both
     * the owner record (legacy filtering) and the owner's user account
     * (so it appears in their notification bell regardless of role).
     */
    public function notifyOwners($property, $owners, $title, $message)
    {
        foreach ($owners as $o) {
            \App\Models\Notification::create([
                'owner_id' => $o->id,
                'user_id' => $o->user_id ?? null,
                'property_id' => $property->id,
                'channel' => 'interne',
                'title' => $title,
                'message' => $message,
                'is_sent' => true,
                'is_read' => false,
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Create an internal notification for the syndic(s) managing the property.
     */
    public function notifySyndic($property, $title, $message)
    {
        $syndics = Syndic::where('property_id', $property->id)->get();

        foreach ($syndics as $syndic) {
            if (! $syndic->user_id) {
                continue;
            }

            \App\Models\Notification::create([
                'owner_id' => null,
                'user_id' => $syndic->user_id,
                'property_id' => $property->id,
                'channel' => 'interne',
                'title' => $title,
                'message' => $message,
                'is_sent' => true,
                'is_read' => false,
                'sent_at' => now(),
            ]);
        }
    }
}