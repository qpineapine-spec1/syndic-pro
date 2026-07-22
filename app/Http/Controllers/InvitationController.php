<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    protected $service;

    public function __construct(InvitationService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'property_id' => ['required', 'exists:properties,id'],
        ]);

        try {
            $user = Auth::user();
            $res = $this->service->createInvitation($data, $user->id);
            return response()->json(['invitation_id' => $res['invitation']->id], 201);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'user_exists') {
                throw ValidationException::withMessages(['email' => 'Un utilisateur existe déjà avec cette adresse email.']);
            }
            throw $e;
        }
    }
}
