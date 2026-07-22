<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\MeetingRequestVote;
use App\Models\Notification;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'syndic') {
            $property = $user->syndic?->property;
            $requests = $property ? MeetingRequest::where('property_id', $property->id)->get() : collect();
        } elseif ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            $requests = $owner ? MeetingRequest::where('property_id', $owner->property_id)->get() : collect();
        } else {
            abort(403);
        }

        // attach vote counts and thresholds
        $requests->transform(function ($r) {
            $r->vote_count = MeetingRequestVote::where('meeting_request_id', $r->id)->count();
            $r->vote_threshold = $r->required_threshold;
            return $r;
        });

        return view('meeting-requests.index', ['requests' => $requests]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'copropriétaire') abort(403);

        $data = $request->validate([
            'title' => 'required|string',
            'motif' => 'nullable|string',
            'property_id' => 'required|integer',
            'type_reunion' => 'required|string|in:assemblee_generale,reunion_conseil,reunion_extraordinaire,autre',
        ]);

        $owner = $user->owner;
        if (! $owner || $owner->property_id !== (int)$data['property_id']) abort(403);

        $ownersCount = Owner::where('property_id', $data['property_id'])->count();
        // Seuil : il faut PLUS d'1/3 des voix favorables pour déclencher la réunion
        $required = max(1, intdiv($ownersCount, 3) + 1);

        $mr = MeetingRequest::create([
            'owner_id' => $owner->id,
            'property_id' => $data['property_id'],
            'title' => $data['title'],
            'description' => $data['motif'] ?? null,
            'type_reunion' => $data['type_reunion'],
            'required_threshold' => $required,
            'votes_for' => 0,
            'status' => 'pending',
        ]);

        return redirect()->route('meetings.index')->with('status', 'Votre demande de réunion a été soumise au vote des copropriétaires.');
    }

    public function vote(Request $request, MeetingRequest $meetingRequest)
    {
        $user = Auth::user();
        if ($user->role !== 'copropriétaire') abort(403);

        $owner = $user->owner;
        if (! $owner || $owner->property_id !== $meetingRequest->property_id) abort(403);

        $exists = MeetingRequestVote::where('meeting_request_id', $meetingRequest->id)->where('owner_id', $owner->id)->exists();
        if ($exists) abort(403);

        MeetingRequestVote::create(['meeting_request_id' => $meetingRequest->id, 'owner_id' => $owner->id]);

        $count = MeetingRequestVote::where('meeting_request_id', $meetingRequest->id)->count();
        $meetingRequest->votes_for = $count;

        if ($meetingRequest->meeting_id === null && $meetingRequest->votes_for >= $meetingRequest->required_threshold) {
            $meetingRequest->status = 'triggered';
            $syndic = Syndic::where('property_id', $meetingRequest->property_id)->first();
            if (! $syndic) {
                $placeholder = \App\Models\User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
                $syndic = Syndic::create(['user_id' => $placeholder->id, 'property_id' => $meetingRequest->property_id]);
            }
            $property = Property::findOrFail($meetingRequest->property_id);
            $service = new MeetingService();
            $meeting = $service->createMeetingForProperty(
                $property,
                'Réunion suite demande: ' . $meetingRequest->title,
                now()->addDays(7),
                $meetingRequest->description,
                $syndic?->id,
                $meetingRequest->type_reunion ?? 'reunion_extraordinaire',
                'À définir',
                true
            );

            $meetingRequest->meeting_id = $meeting->id;
            $meetingRequest->triggered_at = now();

            Notification::create([
                'owner_id' => $owner->id,
                'property_id' => $property->id,
                'channel' => 'interne',
                'title' => 'Réunion automatique créée',
                'message' => 'Une réunion extraordinaire a été créée à la suite d’une demande de réunion.',
                'is_sent' => true,
                'sent_at' => now(),
            ]);
        }

        $meetingRequest->save();

        return redirect()->back();
    }
}