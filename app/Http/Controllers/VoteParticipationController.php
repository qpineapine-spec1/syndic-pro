<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\VoteChoice;
use App\Models\VoteParticipation;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteParticipationController extends Controller
{
    public function store(Request $request, Vote $vote)
    {
        $user = Auth::user();
        if ($user->role !== 'copropriétaire') abort(403);

        $owner = Owner::where('user_id', $user->id)->first();
        if (! $owner) abort(403);

        if ($owner->property_id !== $vote->meeting->property_id) abort(403);

        $choiceInput = $request->input('vote_choice_ids', $request->input('vote_choice_id'));
        if (is_string($choiceInput) || is_int($choiceInput)) {
            $choiceInput = [$choiceInput];
        }
        if (! is_array($choiceInput)) {
            abort(403);
        }
        $choiceInput = array_values(array_filter($choiceInput, fn ($value) => $value !== null && $value !== ''));
        $request->merge(['vote_choice_ids' => $choiceInput]);

        $data = $request->validate(['vote_choice_ids' => 'required|array|min:1', 'vote_choice_ids.*' => 'required|integer']);

        $choices = VoteChoice::whereIn('id', $data['vote_choice_ids'])->where('vote_id', $vote->id)->get();
        if ($choices->count() !== count($data['vote_choice_ids'])) {
            abort(403);
        }

        $maxSelections = max(1, (int) ($vote->nb_choix_autorises ?? 1));
        if ($choices->count() > $maxSelections) {
            abort(403);
        }

        $existingParticipationsCount = VoteParticipation::where('owner_id', $owner->id)
            ->whereHas('voteChoice', function ($query) use ($vote) {
                $query->where('vote_id', $vote->id);
            })->count();

        $maxSelections = max(1, (int) ($vote->nb_choix_autorises ?? 1));
        if ($existingParticipationsCount >= $maxSelections) {
            abort(403);
        }

        $participation = null;
        foreach ($choices as $choice) {
            if ($existingParticipationsCount + 1 > $maxSelections) {
                break;
            }

            $participation = VoteParticipation::create([
                'owner_id' => $owner->id,
                'vote_choice_id' => $choice->id,
                'participated_at' => now(),
            ]);
            $existingParticipationsCount++;
        }

        if ($participation) {
            activity()
                ->causedBy($user)
                ->performedOn($participation)
                ->withProperties([
                    'action_type' => 'vote.participation',
                    'property_id' => $owner->property_id,
                ])
                ->log('Vote participation created');
        }

        return redirect()->back();
    }
}
