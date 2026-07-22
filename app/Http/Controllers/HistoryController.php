<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        $query = Activity::query();
        $query->whereJsonContains('properties->property_id', $user->syndic?->property_id);

        if ($request->filled('type')) {
            $query->whereJsonContains('properties->action_type', $request->input('type'));
        }

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->input('user_id'));
        }

        $activities = $query->latest()->paginate(15);

        return view('history.index', [
            'activities' => $activities,
            'filterType' => $request->input('type'),
            'filterUserId' => $request->input('user_id'),
        ]);
    }
}
