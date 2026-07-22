<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Owner;
use App\Models\Property;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    private const ATTACHMENT_RULES = 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120';

    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'syndic') {
            $property = $user->syndic?->property;
            $complaints = $property
                ? Complaint::with('owner.user')
                    ->where('property_id', $property->id)
                    ->latest('date')
                    ->latest('id')
                    ->get()
                : collect();
        } elseif ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            $complaints = $owner
                ? Complaint::with('owner.user')
                    ->where('owner_id', $owner->id)
                    ->latest('date')
                    ->latest('id')
                    ->get()
                : collect();
        } else {
            abort(403);
        }

        return view('complaints.index', compact('complaints'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'copropriétaire') {
            abort(403);
        }

        $request->merge([
            'motif' => $request->input('motif', $request->input('subject')),
            'subject' => $request->input('subject', $request->input('motif')),
        ]);

        $data = $request->validate([
            'owner_id' => 'required|integer',
            'property_id' => 'required|integer',
            'motif' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|string|in:faible,normale,elevee',
            'fichier_joint' => 'nullable|' . self::ATTACHMENT_RULES,
        ]);

        $subjectValue = $data['subject'] ?? $data['motif'] ?? null;
        if (blank($subjectValue)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['subject' => 'Le motif est requis.']);
        }

        // Cast explicite : $request->validate() renvoie des strings HTTP
        // même pour la règle "integer". Sans ce cast, la comparaison
        // stricte plus bas échouait toujours en conditions réelles
        // (string "5" !== int 5), empêchant la création de réclamation
        // et donc son apparition côté syndic.
        $ownerId = (int) $data['owner_id'];
        $propertyId = (int) $data['property_id'];

        $owner = Owner::findOrFail($ownerId);
        if ($owner->user_id !== $user->id) {
            abort(403);
        }

        if ($owner->property_id !== $propertyId) {
            abort(403);
        }

        $path = null;
        if ($request->hasFile('fichier_joint')) {
            $path = $request->file('fichier_joint')->store('complaints', 'public');
        }

        $complaint = Complaint::create([
            'owner_id' => $owner->id,
            'property_id' => $propertyId,
            'subject' => $subjectValue,
            'description' => $data['description'] ?? null,
            'status' => 'nouvelle',
            'priority' => $data['priority'] ?? 'normale',
            'fichier_joint' => $path,
            'category' => $data['category'] ?? 'autre',
            'date' => $data['date'] ?? now()->toDateString(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($complaint)
            ->withProperties([
                'action_type' => 'complaint.create',
                'property_id' => $complaint->property_id,
            ])
            ->log('Complaint created');

        // Notifie le syndic qu'une nouvelle réclamation vient d'être déposée.
        $property = Property::find($propertyId);
        if ($property) {
            (new MeetingService())->notifySyndic(
                $property,
                'Nouvelle réclamation',
                'Une nouvelle réclamation a été déposée par ' . ($owner->user?->name ?? 'un copropriétaire') . ' : ' . $complaint->subject
            );
        }

        return redirect()->route('complaints.index')->with('success', 'Réclamation enregistrée.');
    }

    public function updateStatus(Request $request, Complaint $complaint)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($complaint->property_id !== $user->syndic?->property_id) {
            abort(403);
        }

        $data = $request->validate(['status' => 'required|string|in:nouvelle,en_cours,finie,annulee,open,in_progress,closed']);
        $normalizedStatus = match ($data['status']) {
            'open', 'nouvelle' => 'nouvelle',
            'in_progress', 'en_cours' => 'en_cours',
            'closed', 'finie' => 'finie',
            'annulee' => 'annulee',
            default => 'nouvelle',
        };

        $complaint->status = $normalizedStatus;
        $complaint->save();

        activity()
            ->causedBy($user)
            ->performedOn($complaint)
            ->withProperties([
                'action_type' => 'complaint.status_update',
                'property_id' => $complaint->property_id,
                'new_status' => $complaint->status,
            ])
            ->log('Complaint status updated');

        return redirect()->route('complaints.index')->with('success', 'Statut mis à jour.');
    }

    /**
     * Le copropriétaire confirme que sa réclamation, marquée "finie" par le
     * syndic, est effectivement résolue.
     */
    public function validateByOwner(Complaint $complaint)
    {
        $user = Auth::user();

        if ($user->role !== 'copropriétaire') {
            abort(403);
        }

        if (! $complaint->owner || $complaint->owner->user_id !== $user->id) {
            abort(403);
        }

        if (! $complaint->canBeValidatedByOwner()) {
            return redirect()->route('complaints.index')
                ->with('error', 'Cette réclamation ne peut être validée que lorsque le syndic l’a marquée comme terminée.');
        }

        $complaint->status = 'validee';
        $complaint->validated_at = now();
        $complaint->save();

        activity()
            ->causedBy($user)
            ->performedOn($complaint)
            ->withProperties([
                'action_type' => 'complaint.validated_by_owner',
                'property_id' => $complaint->property_id,
            ])
            ->log('Complaint validated by owner');

        return redirect()->route('complaints.index')->with('success', 'Réclamation validée. Merci pour votre retour.');
    }

    public function uploadAttachment(Request $request, Complaint $complaint)
    {
        $user = Auth::user();

        if (! $this->userCanAccessComplaint($user, $complaint)) {
            abort(403);
        }

        $data = $request->validate(['fichier_joint' => 'required|' . self::ATTACHMENT_RULES]);

        if ($complaint->fichier_joint && Storage::disk('public')->exists($complaint->fichier_joint)) {
            Storage::disk('public')->delete($complaint->fichier_joint);
        }

        $path = $request->file('fichier_joint')->store('complaints', 'public');

        $complaint->fichier_joint = $path;
        $complaint->save();

        return redirect()->route('complaints.index')->with('success', 'Pièce jointe téléchargée.');
    }

    public function downloadAttachment(Complaint $complaint)
    {
        $user = Auth::user();

        if (! $this->userCanAccessComplaint($user, $complaint)) {
            abort(403);
        }

        if (! $complaint->fichier_joint || ! Storage::disk('public')->exists($complaint->fichier_joint)) {
            abort(404);
        }

        return Storage::disk('public')->download($complaint->fichier_joint);
    }

    private function userCanAccessComplaint($user, Complaint $complaint): bool
    {
        if ($user->role === 'copropriétaire') {
            return $complaint->owner && $complaint->owner->user_id === $user->id;
        }

        if ($user->role === 'syndic') {
            return $complaint->property_id === $user->syndic?->property_id;
        }

        return false;
    }
}