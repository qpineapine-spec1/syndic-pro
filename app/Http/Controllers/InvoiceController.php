<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'syndic') {
            $invoices = Invoice::where('property_id', $user->syndic?->property_id)->get();
        } elseif ($user->role === 'copropriétaire') {
            // Council members can view all invoices for their property (read-only).
            if ($user->owner && $user->owner->is_council_member) {
                $invoices = Invoice::where('property_id', $user->owner->property_id)->get();
            } else {
                // Regular owners see only their own invoices.
                $invoices = Invoice::where('owner_id', $user->owner?->id)->get();
            }
        } else {
            abort(403);
        }

        return view('invoices.index', compact('invoices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'contribution_id' => 'required|integer|exists:contributions,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        if ($user->role !== 'syndic' || !$user->syndic) {
            abort(403);
        }

        $contribution = Contribution::with('owner')->findOrFail($request->contribution_id);
        $owner = $contribution->owner;

        if (!$owner || $owner->property_id !== $user->syndic->property_id) {
            abort(403);
        }

        Invoice::create([
            'contribution_id' => $contribution->id,
            'owner_id' => $owner->id,
            'property_id' => $owner->property_id,
            'invoice_number' => $request->invoice_number,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'amount' => $request->amount,
            'status' => 'non_payee',
        ]);

        return redirect()->back();
    }

    // Create invoice from a contribution (route used by tests)
    public function createFromContribution(Contribution $contribution)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic' || !$user->syndic) {
            abort(403);
        }

        $owner = $contribution->owner;
        if (!$owner || $owner->property_id !== $user->syndic->property_id) {
            abort(403);
        }

        $invoice = Invoice::create([
            'contribution_id' => $contribution->id,
            'owner_id' => $owner->id,
            'property_id' => $owner->property_id,
            'invoice_number' => 'INV-' . ($contribution->id ?? now()->timestamp),
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'amount' => $contribution->montant_annuel ?? 0,
            'status' => 'non_payee',
        ]);

        return response()->json($invoice, 201);
    }

    public function markAsPaid(Invoice $invoice)
    {
        $user = Auth::user();

        $invoicePropertyId = $invoice->property_id;
        if (!$invoicePropertyId && $invoice->contribution) {
            $invoicePropertyId = $invoice->contribution->owner->property_id ?? null;
        }

        if ($user->role !== 'syndic' || !$user->syndic || $invoicePropertyId !== $user->syndic->property_id) {
            abort(403);
        }

        $invoice->status = 'paid';
        $invoice->payment_date = now();
        $invoice->save();

        return response()->json($invoice, 200);
    }

    // Return invoices for a given owner as JSON (used by tests)
    public function forOwner(
        \App\Models\Owner $owner
    ) {
        $invoices = Invoice::where('owner_id', $owner->id)->get();
        return response()->json($invoices);
    }
}
