<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\OwnerInvitation;
use App\Services\PdfImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    protected $parser;

    public function __construct(PdfImportService $parser)
    {
        $this->parser = $parser;
    }

    public function showUploadForm()
    {
        $user = Auth::user();
        $syndic = \App\Models\Syndic::where('user_id', $user->id)->first();
        $property = null;
        $alreadyImported = false;
        $importedAt = null;
        if ($syndic && $syndic->property_id) {
            $property = \App\Models\Property::find($syndic->property_id);
            if ($property) {
                $importedAt = $property->imported_at ?? null;
                $alreadyImported = !is_null($importedAt);
            }
        }

        return view('import.upload', ['alreadyImported' => $alreadyImported, 'importedAt' => $importedAt]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf'],
        ]);

        // Protect against accidental re-imports: require explicit 'force' when property already imported
        $user = Auth::user();
        $syndic = \App\Models\Syndic::where('user_id', $user->id)->first();
        $propertyId = $syndic->property_id ?? null;
        if ($propertyId) {
            $property = \App\Models\Property::find($propertyId);
            if ($property && $property->imported_at && !$request->boolean('force')) {
                throw ValidationException::withMessages(['pdf' => 'Un import a déjà été effectué pour cette propriété le ' . ($property->imported_at?->format('d/m/Y à H:i') ?? $property->imported_at) . '. Cochez la case pour confirmer que vous comprenez les risques.']);
            }
        }

        $path = $request->file('pdf')->store('imports');
        $fullPath = storage_path('app/' . $path);

        $data = $this->parser->parse($fullPath);

        // store parsed data in session for confirmation
        $request->session()->put('import.parsed', $data);
        $request->session()->put('import.uploaded_path', $path);

        return view('import.preview', ['data' => $data]);
    }

    public function confirm(Request $request)
    {
        $data = $request->session()->get('import.parsed');
        if (!$data) {
            throw ValidationException::withMessages(['pdf' => 'No parsed data in session.']);
        }

        $user = Auth::user();
        $syndic = \App\Models\Syndic::where('user_id', $user->id)->first();
        $propertyId = $syndic->property_id ?? null;
        if (!$propertyId) {
            throw ValidationException::withMessages(['user' => 'User has no property context.']);
        }

        $results = [
            'owners_created' => [],
            'owners_skipped' => [],
            'service_providers' => [],
            'budget' => null,
            'expenses' => [],
            'warnings' => [],
        ];

        // Owners -> create OwnerInvitation for each, reuse InvitationService
        $invService = new \App\Services\InvitationService();
        foreach ($data['owners'] as $owner) {
            $email = $owner['email'] ?? null;
            $phone = $owner['phone'] ?? null;

            try {
                $res = $invService->createInvitation([
                    'email' => $email,
                    'phone' => $phone,
                    'property_id' => $propertyId,
                    'created_by' => (int) ($user->id ?? auth()->id()),
                ], (int) ($user->id ?? auth()->id()));
                $results['owners_created'][] = $res['invitation']->id;
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'user_exists') {
                    $results['owners_skipped'][] = ['email' => $email, 'reason' => 'user_exists'];
                    continue;
                }
                throw $e;
            }
        }

        // Service providers
        foreach ($data['service_providers'] as $sp) {
            $model = ServiceProvider::create([
                'property_id' => $propertyId,
                'name' => $sp['name'] ?? null,
                'specialty' => null,
                'contract_reference' => null,
                'contract_start_date' => $sp['contract_start'] ? Carbon::createFromFormat('d/m/Y', $sp['contract_start'])->format('Y-m-d') : null,
                'contract_end_date' => $sp['contract_end'] ? Carbon::createFromFormat('d/m/Y', $sp['contract_end'])->format('Y-m-d') : null,
                'alert_expiration_days' => 30,
                'status' => 'active',
            ]);
            $results['service_providers'][] = $model->id;
        }

        // Compute totals from parsed data first
        $fixedTotal = 0;
        if (!empty($data['expenses_fixes'])) {
            foreach ($data['expenses_fixes'] as $ef) {
                $amount = $ef['annual'] ?? $ef['monthly'] ?? 0;
                $fixedTotal += (float) ($amount ?: 0);
            }
        }

        $variableTotal = 0;
        if (!empty($data['expenses_variables'])) {
            foreach ($data['expenses_variables'] as $ev) {
                $amount = $ev['annual_estimate'] ?? 0;
                $variableTotal += (float) ($amount ?: 0);
            }
        }

        // Budget
        if (!empty($data['budget'])) {
            // use syndic record id for foreign key
            $syndicModel = \App\Models\Syndic::where('user_id', $user->id)->first();
            $syndicId = $syndicModel->id ?? null;

            $budget = Budget::create([
                'property_id' => $propertyId,
                'syndic_id' => $syndicId,
                'year' => $data['budget']['year'] ?? null,
                'is_valid' => false,
                'fixed_charges_total' => $fixedTotal,
                'variable_charges_total' => $variableTotal,
            ]);
            $results['budget'] = $budget->id;
        }

        // Create expense records after budget exists
        if (!empty($data['expenses_fixes'])) {
            foreach ($data['expenses_fixes'] as $ef) {
                $amount = $ef['annual'] ?? $ef['monthly'] ?? null;
                $e = Expense::create([
                    'property_id' => $propertyId,
                    'service_provider_id' => null,
                    'owner_id' => null,
                    'label' => $ef['categorie'] ?? null,
                    'categorie' => $ef['categorie'] ?? null,
                    'amount' => $amount,
                    'montant_mensuel' => $ef['monthly'] ?? null,
                    'justificatif_pdf' => $ef['justificatif'] ?? null,
                    'expense_date' => now(),
                    'type' => 'fixe',
                    'status' => 'imported',
                    'fichier_facture' => null,
                ]);
                $results['expenses'][] = $e->id;
            }
        }

        if (!empty($data['expenses_variables'])) {
            foreach ($data['expenses_variables'] as $ev) {
                $amount = $ev['annual_estimate'] ?? null;
                $e = Expense::create([
                    'property_id' => $propertyId,
                    'service_provider_id' => null,
                    'owner_id' => null,
                    'label' => ($ev['type'] ?? '') . ' - ' . ($ev['categorie'] ?? ''),
                    'categorie' => $ev['categorie'] ?? null,
                    'amount' => $amount,
                    'montant_mensuel' => null,
                    'justificatif_pdf' => $ev['justificatif'] ?? null,
                    'expense_date' => now(),
                    'type' => 'variable',
                    'status' => 'imported',
                    'fichier_facture' => null,
                ]);
                $results['expenses'][] = $e->id;
            }
        }

        if (!empty($data['totals'])) {
            $declaredFixed = $data['totals']['fixed'] ?? null;
            $declaredVariable = $data['totals']['variable'] ?? null;
            $declaredTotal = $data['totals']['total'] ?? null;

            if ($declaredFixed !== null && $declaredFixed > 0) {
                $ratio = abs($fixedTotal - $declaredFixed) / max($declaredFixed, 1);
                if ($ratio > 0.01) {
                    $results['warnings'][] = 'fixed_total_mismatch';
                }
            }

            if ($declaredVariable !== null && $declaredVariable > 0) {
                $ratio = abs($variableTotal - $declaredVariable) / max($declaredVariable, 1);
                if ($ratio > 0.01) {
                    $results['warnings'][] = 'variable_total_mismatch';
                }
            }

            if ($declaredTotal !== null) {
                $computedTotal = $fixedTotal + $variableTotal;
                $ratio = abs($computedTotal - $declaredTotal) / max($declaredTotal, 1);
                if ($ratio > 0.01) {
                    $results['warnings'][] = 'budget_total_mismatch';
                }
            }
        }

        // clear session import
        $request->session()->forget('import');

        // mark property as imported
        try {
            if (isset($propertyId) && $propertyId) {
                $prop = \App\Models\Property::find($propertyId);
                if ($prop && !$prop->imported_at) {
                    $prop->imported_at = now();
                    $prop->save();
                }
            }
        } catch (\Throwable $e) {
            // don't break the response if marking fails, but log in production
        }

        return view('import.confirmed', ['results' => $results]);
    }
}
