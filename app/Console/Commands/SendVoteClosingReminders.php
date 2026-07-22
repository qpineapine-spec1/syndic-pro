<?php

namespace App\Console\Commands;

use App\Models\Owner;
use App\Models\Vote;
use App\Services\MeetingService;
use Illuminate\Console\Command;

class SendVoteClosingReminders extends Command
{
    protected $signature = 'vote:send-closing-reminders';
    protected $description = 'Send internal reminders 2 hours before vote closing to owners who did not participate yet.';

    public function handle()
    {
        $now = now();
        $limit = now()->addHours(2);

        $votes = Vote::whereNull('reminder_sent_at')
            ->where('status', 'open')
            ->where('ends_at', '>=', $now)
            ->where('ends_at', '<=', $limit)
            ->get();

        $service = new MeetingService();

        foreach ($votes as $vote) {
            $meeting = $vote->meeting;
            if (!$meeting) continue;
            $property = $meeting->property;
            $owners = Owner::where('property_id', $property->id)->get();

            $toNotify = [];
            foreach ($owners as $owner) {
                $participated = \App\Models\VoteParticipation::where('owner_id', $owner->id)
                    ->whereIn('vote_choice_id', $vote->voteChoices()->pluck('id'))
                    ->exists();
                if (!$participated) $toNotify[] = $owner;
            }

            if (!empty($toNotify)) {
                $title = 'Rappel de clôture de vote: ' . $vote->question;
                $message = 'Le vote se termine le ' . $vote->ends_at;
                $service->notifyOwners($property, $toNotify, $title, $message);
            }

            $vote->reminder_sent_at = now();
            $vote->save();
        }

        return 0;
    }
}
