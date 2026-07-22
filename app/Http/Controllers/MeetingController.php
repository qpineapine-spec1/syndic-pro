<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Notification;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MeetingService;
use App\Services\MeetingMinutesTemplateService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Ensure PhpWord is available for template generation
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class MeetingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $meetingRequests = collect();
        $votedRequestIds = collect();

        if ($user->role === 'syndic') {
            $property = $user->syndic?->property;
            $meetings = $property ? Meeting::where('property_id', $property->id)->get() : collect();
        } elseif ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            $meetings = $owner ? Meeting::where('property_id', $owner->property_id)->get() : collect();

            if ($owner) {
                $meetingRequests = \App\Models\MeetingRequest::where('property_id', $owner->property_id)
                    ->where('status', 'pending')
                    ->orderByDesc('created_at')
                    ->get();

                $meetingRequests->each(function ($r) {
                    $r->vote_count = \App\Models\MeetingRequestVote::where('meeting_request_id', $r->id)->count();
                });

                $votedRequestIds = \App\Models\MeetingRequestVote::where('owner_id', $owner->id)
                    ->whereIn('meeting_request_id', $meetingRequests->pluck('id'))
                    ->pluck('meeting_request_id');
            }
        } else {
            abort(403);
        }

        return view('meetings.index', compact('meetings', 'meetingRequests', 'votedRequestIds'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string',
            'type_reunion' => 'nullable|string|in:assemblee_generale,reunion_conseil,reunion_extraordinaire,autre',
            'meeting_date' => 'required|date',
            'agenda' => 'nullable|string',
            'lieu' => 'nullable|string',
            'notify_owners' => 'nullable|boolean',
            'property_id' => 'required|integer',
        ]);

        $property = $user->syndic?->property;
        if (! $property || $property->id !== (int)$data['property_id']) {
            abort(403);
        }

        $service = new MeetingService();
        $meeting = $service->createMeetingForProperty(
            $property,
            $data['title'],
            $data['meeting_date'],
            $data['agenda'] ?? null,
            $user->syndic->id,
            $data['type_reunion'] ?? 'assemblee_generale',
            $data['lieu'] ?? null,
            $data['notify_owners'] ?? true
        );

        return redirect()->route('meetings.index')->with('success', 'Réunion créée.');
    }

    public function show(Meeting $meeting)
    {
        $user = Auth::user();
        if ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            if (! $owner || $owner->property_id !== $meeting->property_id) abort(403);
        } elseif ($user->role === 'syndic') {
            if ($user->syndic?->property_id !== $meeting->property_id) abort(403);
        } else {
            abort(403);
        }

        $meeting->load(['votes.voteChoices.voteParticipations.owner']);

        return view('meetings.show', compact('meeting'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic' || $user->syndic?->property_id !== $meeting->property_id) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string',
            'type_reunion' => 'nullable|string|in:assemblee_generale,reunion_conseil,reunion_extraordinaire,autre',
            'meeting_date' => 'required|date',
            'agenda' => 'nullable|string',
            'lieu' => 'nullable|string',
            'status' => 'nullable|string|in:scheduled,annulee,closed',
            'property_id' => 'required|integer',
        ]);

        $meeting->fill([
            'title' => $data['title'],
            'type_reunion' => $data['type_reunion'] ?? $meeting->type_reunion,
            'meeting_date' => $data['meeting_date'],
            'agenda' => $data['agenda'] ?? $meeting->agenda,
            'lieu' => $data['lieu'] ?? $meeting->lieu,
            'status' => $data['status'] ?? $meeting->status,
        ]);
        $meeting->save();

        $this->notifyOwners($meeting, 'Réunion modifiée: ' . $meeting->title, 'La réunion a été mise à jour.');

        return redirect()->route('meetings.show', $meeting)->with('success', 'Réunion mise à jour.');
    }

    public function cancel(Meeting $meeting)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic' || $user->syndic?->property_id !== $meeting->property_id) {
            abort(403);
        }

        $meeting->status = 'annulee';
        $meeting->save();

        $this->notifyOwners($meeting, 'Réunion annulée: ' . $meeting->title, 'La réunion a été annulée.');

        return redirect()->route('meetings.show', $meeting)->with('success', 'Réunion annulée.');
    }

    private function notifyOwners(Meeting $meeting, string $title, string $message): void
    {
        $owners = Owner::where('property_id', $meeting->property_id)->get();
        foreach ($owners as $owner) {
            Notification::create([
                'owner_id' => $owner->id,
                'property_id' => $meeting->property_id,
                'channel' => 'interne',
                'title' => $title,
                'message' => $message,
                'is_sent' => true,
                'sent_at' => now(),
            ]);
        }
    }

    public function downloadReport(Meeting $meeting)
    {
        $user = Auth::user();
        if ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            if (! $owner || $owner->property_id !== $meeting->property_id) {
                abort(403);
            }
        } elseif ($user->role === 'syndic') {
            if ($user->syndic?->property_id !== $meeting->property_id) {
                abort(403);
            }
        } else {
            abort(403);
        }

        if (! $meeting->compte_rendu) {
            abort(404);
        }

        $path = storage_path('app/public/' . $meeting->compte_rendu);
        if (! file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function downloadReportTemplate(Meeting $meeting)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') abort(403);
        if ($user->syndic?->property_id !== $meeting->property_id) abort(403);

        if (!class_exists('\PhpOffice\\PhpWord\\PhpWord')) {
            abort(500, 'phpoffice/phpword is required');
        }

        $phpWord = new PhpWord();
        $templateService = new MeetingMinutesTemplateService();
        $templateService->generate($phpWord, $meeting);

        Storage::makeDirectory('templates');
        $filename = 'templates/modele_premiere_assemblee_' . $meeting->id . '.docx';
        $fullPath = storage_path('app/' . $filename);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return response()->download($fullPath, 'compte-rendu-meeting-' . $meeting->id . '.docx');
    }

    public function uploadReport(Request $request, Meeting $meeting)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') abort(403);
        if ($user->syndic?->property_id !== $meeting->property_id) abort(403);

        $data = $request->validate([
            'report' => 'required|file',
        ]);

        $path = $request->file('report')->store('reports', 'public');
        $meeting->compte_rendu = $path;
        $meeting->save();

        return redirect()->back();
    }
}