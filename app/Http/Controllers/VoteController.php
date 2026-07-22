<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\Meeting;
use App\Models\VoteChoice;
use App\Models\VoteParticipation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') abort(403);

        $meetingId = $request->input('meeting_id');
        if ($meetingId !== null) {
            $meeting = Meeting::find($meetingId);
            if ($meeting && $meeting->property_id !== $user->syndic?->property_id) {
                abort(403);
            }
        }

        $rawChoices = $request->input('choices');
        if (is_string($rawChoices)) {
            $choices = preg_split('/\r\n|\r|\n/', trim($rawChoices));
            $choices = array_values(array_filter(array_map('trim', $choices), fn ($choice) => $choice !== ''));
            $request->merge(['choices' => $choices]);
        }

        $data = $request->validate([
            'meeting_id' => 'required|integer',
            'question' => 'required|string',
            'choices' => 'required|array|min:1',
            'choices.*' => 'required|string|max:255',
            'vote_type' => 'nullable|in:single,multiple',
            'nb_choix_autorises' => 'nullable|integer|min:2|max:10',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $meeting = Meeting::findOrFail($data['meeting_id']);
        $voteType = $data['vote_type'] ?? 'single';
        $maxSelections = $voteType === 'multiple' ? (int) ($data['nb_choix_autorises'] ?? 2) : 1;

        $vote = Vote::create([
            'meeting_id' => $meeting->id,
            'question' => $data['question'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'nb_choix_autorises' => $maxSelections,
            'status' => 'open',
        ]);

        foreach ($data['choices'] as $index => $label) {
            VoteChoice::create([
                'vote_id' => $vote->id,
                'label' => trim($label),
                'description' => $index === 0 ? 'Choix principal' : null,
            ]);
        }

        return redirect()->back();
    }

    public function close(Vote $vote)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') abort(403);

        if ($vote->meeting->property_id !== $user->syndic?->property_id) abort(403);

        $vote->status = 'closed';
        $vote->final_decision = $this->resolveWinner($vote);
        $vote->save();

        return redirect()->back();
    }

    public function results(Vote $vote)
    {
        $results = $vote->voteChoices()->withCount('voteParticipations')->get()->mapWithKeys(function ($c) {
            return [$c->label => $c->vote_participations_count];
        });

        return response()->json([
            'results' => $results,
            'winner' => $vote->final_decision,
            'status' => $vote->status,
        ]);
    }

    private function resolveWinner(Vote $vote): string
    {
        $choices = $vote->voteChoices()->withCount('voteParticipations')->get();
        if ($choices->isEmpty()) {
            return 'Aucun choix';
        }

        $winner = $choices->sortByDesc(function ($choice) {
            return $choice->vote_participations_count;
        })->first();

        return $winner?->label ?? 'Aucun choix';
    }
}
