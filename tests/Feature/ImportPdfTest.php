<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\Owner;
use App\Models\OwnerInvitation;
use App\Models\Property;
use App\Models\ServiceProvider;
use App\Models\Syndic;
use App\Models\User;
use App\Services\PdfImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ImportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_preview_and_confirm_imports_expected_records()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Imp Prop', 'address' => '1 Test']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        // Bind a fake parser returning structured data
        $fakeParsed = [
            'owners' => [
                ['last_name' => 'Dupont', 'first_name' => 'Jean', 'email' => 'dupont@example.com', 'phone' => '0102030405', 'lot_surface' => '45', 'floor' => '2', 'surface' => '45', 'status' => 'Propriétaire'],
                ['last_name' => 'Martin', 'first_name' => 'Claire', 'email' => 'martin@example.com', 'phone' => '0605040302', 'lot_surface' => '30', 'floor' => '1', 'surface' => '30', 'status' => 'Propriétaire'],
            ],
            'service_providers' => [
                ['name' => 'Nettoyage SARL', 'contract_start' => '01/01/2024', 'contract_end' => '31/12/2024', 'monthly_amount' => '300', 'visits_per_month' => '8'],
            ],
            'budget' => ['year' => '2026'],
            'expenses_fixes' => [
                ['categorie' => 'Electricite', 'monthly' => 100, 'annual' => 1200, 'justificatif' => null],
            ],
            'expenses_variables' => [
                ['type' => 'Reparation', 'categorie' => 'Ascenseur', 'annual_estimate' => 5000, 'justificatif' => null],
            ],
        ];

        $this->app->instance(PdfImportService::class, new class($fakeParsed) extends PdfImportService {
            private $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function parse(string $path): array
            {
                return $this->data;
            }
        });

        $fixturePath = base_path('tests/Fixtures/premiere-assemblee-exemple.pdf');
        $file = new \Illuminate\Http\UploadedFile($fixturePath, 'pv.pdf', 'application/pdf', null, true);

        $resp = $this->actingAs($syndicUser)->post(route('import.preview'), ['pdf' => $file]);
        $resp->assertStatus(200);
        $resp->assertSeeText('Dupont');
        $resp->assertSeeText('Nettoyage SARL');

        // Confirm import
        $confirm = $this->actingAs($syndicUser)->post(route('import.confirm'));
        $confirm->assertStatus(200);
        $confirm->assertSee('Retour au tableau de bord');
        $this->assertDatabaseCount('owner_invitations', 2);
        $this->assertDatabaseHas('service_providers', ['name' => 'Nettoyage SARL']);
        $this->assertDatabaseHas('budgets', ['year' => '2026', 'is_valid' => 0]);
        $this->assertDatabaseHas('expenses', ['label' => 'Electricite']);

        // ensure no User/Owner created directly
        $this->assertDatabaseMissing('users', ['email' => 'dupont@example.com']);
        $this->assertDatabaseMissing('owners', ['property_id' => $property->id, 'lot_surface' => '45']);

        // property should be marked as imported
        $prop = Property::find($property->id);
        $this->assertNotNull($prop->imported_at);

        // a second preview without forcing should be blocked (JSON request)
        $resp2 = $this->actingAs($syndicUser)->postJson(route('import.preview'), ['pdf' => $file]);
        $resp2->assertStatus(422);

        // forcing the preview should work
        $resp3 = $this->actingAs($syndicUser)->post(route('import.preview'), ['pdf' => $file, 'force' => '1']);
        $resp3->assertStatus(200);
    }

    public function test_pdf_import_service_parses_real_pdf_file()
    {
        $lines = [
            'SYNDIC PROFESSIONNEL - PROCES-VERBAL DE PREMIERE ASSEMBLEE',
            'Immeuble : Test',
            'Adresse : 1 Rue Exemple',
            'Date de l\'assemblee : 01/01/2026',
            '',
            'LISTE DES COPROPRIETAIRES',
            'Nom | Prenom | Email | Telephone | Numero bureau | Etage | Superficie lot | Statut',
            'Dupont | Jean | dupont@example.com | 0101010101 | 101 | 2 | 45 | Proprietaire',
            'Martin | Claire | martin@example.com | 0202020202 | 102 | 1 | 30 | Proprietaire',
            '',
            'LISTE DES PRESTATAIRES',
            'Nom societe | Date debut contrat | Date fin contrat | Montant mensuel | Nb visites mois',
            'Nettoyage SARL | 01/01/2024 | 31/12/2024 | 300 | 8',
            '',
            'BUDGET PREVISIONNEL',
            'Annee : 2026',
            '',
            'CHARGES FIXES',
            'Categorie | Montant mensuel | Montant annuel | Justificatif',
            'Electricite parties communes | 100 | 1200 |',
            'Eau parties communes | 50 | 600 |',
            '',
            'CHARGES VARIABLES',
            'Type | Categorie | Montant estime annuel | Justificatif',
            'Reparation | Ascenseur | 5000 |',
            'Travaux | Ravalement | 15000 |',
            '',
            'TOTAL CHARGES FIXES ANNUELLES : 1800',
            'TOTAL CHARGES VARIABLES ANNUELLES : 20000',
            'MONTANT TOTAL BUDGET : 22000',
        ];

        $fixturePath = base_path('tests/Fixtures/premiere-assemblee-exemple.pdf');
        $service = new PdfImportService();
        $result = $service->parse($fixturePath);

        // Updated expectations to match the real fixture (modele-premiere-assemblee-test.pdf)
        $this->assertCount(3, $result['owners']);
        $this->assertSame('Martin', $result['owners'][0]['last_name']);
        $this->assertSame('amine3454349@gmail.com', $result['owners'][0]['email']);
        $this->assertSame('Dubois', $result['owners'][1]['last_name']);
        $this->assertSame('python.world.dev2024@gmail.com', $result['owners'][1]['email']);
        $this->assertSame('Bernard', $result['owners'][2]['last_name']);
        $this->assertSame('trendazz2024@gmail.com', $result['owners'][2]['email']);

        $this->assertCount(6, $result['service_providers']);
        $this->assertSame('Nettoyage Pro SARL', $result['service_providers'][0]['name']);
        $this->assertSame('Gardiennage Plus', $result['service_providers'][5]['name']);

        $this->assertSame('2026', $result['budget']['year']);
        $this->assertCount(6, $result['expenses_fixes']);
        $this->assertCount(6, $result['expenses_variables']);
        $this->assertNull($result['totals']['fixed']);
        $this->assertNull($result['totals']['variable']);
        $this->assertNull($result['totals']['total']);

        // no temp file to remove when using committed fixture
    }



    private function createPdfFixture(array $lines): string
    {
        $streamLines = array_map(function (string $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            return "({$escaped}) Tj";
        }, $lines);

        $stream = "BT\n/F1 12 Tf\n72 760 Td\n" . implode(" T*\n", $streamLines) . "\nET\n";
        $objects = [
            "%PDF-1.4\n",
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = '';
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF\n";

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('import_pdf_', true) . '.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }

    public function test_account_activation_persists_phone_from_import_invitation()
    {
        $property = Property::create(['name' => 'Imp Prop', 'address' => '1 Test']);
        $token = 'import-token-' . uniqid();
        $invitation = OwnerInvitation::create([
            'email' => 'invite@example.com',
            'phone' => '0102030405',
            'property_id' => $property->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(48),
            'created_by' => null,
        ]);

        $response = $this->post(route('activate.store', ['token' => $token]), [
            'name' => 'Jean Dupont',
            'email' => 'invite@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'status' => 'proprietaire',
            'is_tenant' => false,
            'lot_surface' => 45,
            'surface_confirmation' => 45,
            'has_mezzanine' => false,
            'office_number' => '101',
            'floor' => '2',
            'is_council_member' => false,
        ]);

        $response->assertRedirect(route('login'));
        $owner = Owner::where('property_id', $property->id)->first();
        $this->assertNotNull($owner);
        $this->assertSame('0102030405', $owner->telephone);
    }

    public function test_import_confirmation_maps_owner_phone_to_telephone_column(): void
    {
        $property = Property::create(['name' => 'Imp Prop', 'address' => '1 Test']);
        $user = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);

        $session = app('session.store');
        $session->put('import.parsed', [
            'owners' => [['email' => 'owner@example.com', 'phone' => '0606060606']],
            'service_providers' => [],
            'budget' => [],
            'expenses_fixes' => [],
            'expenses_variables' => [],
            'totals' => [],
        ]);

        $this->actingAs($user)->post(route('import.confirm'));

        $invitation = 
            \App\Models\OwnerInvitation::where('property_id', $property->id)->latest()->first();
        $this->assertNotNull($invitation);
        $this->assertSame('0606060606', $invitation->phone);
    }

    public function test_import_stores_exact_pdf_data_in_expense_columns()
    {
        Mail::fake();

        $syndicUser = User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()]);
        $property = Property::create(['name' => 'Imp Prop', 'address' => '1 Test']);
        Syndic::create(['user_id' => $syndicUser->id, 'property_id' => $property->id]);

        // Bind fake parser with comprehensive PDF data
        $fakeParsed = [
            'owners' => [
                ['last_name' => 'Dupont', 'first_name' => 'Jean', 'email' => 'dupont@example.com', 'phone' => '0102030405', 'lot_surface' => '45', 'floor' => '2', 'surface' => '45', 'status' => 'Propriétaire'],
            ],
            'service_providers' => [],
            'budget' => ['year' => '2026'],
            'expenses_fixes' => [
                ['categorie' => 'Electricité parties communes', 'monthly' => 100.50, 'annual' => 1206.00, 'justificatif' => 'EDF contrat 2026'],
                ['categorie' => 'Eau parties communes', 'monthly' => 50.00, 'annual' => 600.00, 'justificatif' => 'Veolia'],
            ],
            'expenses_variables' => [
                ['type' => 'Réparation', 'categorie' => 'Ascenseur', 'annual_estimate' => 5000.00, 'justificatif' => 'Maintenance annuelle'],
                ['type' => 'Travaux', 'categorie' => 'Ravalement', 'annual_estimate' => 15000.00, 'justificatif' => 'Devis Entreprise X'],
            ],
        ];

        $this->app->instance(PdfImportService::class, new class($fakeParsed) extends PdfImportService {
            private $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function parse(string $path): array
            {
                return $this->data;
            }
        });

        $fixturePath = base_path('tests/Fixtures/premiere-assemblee-exemple.pdf');
        $file = new \Illuminate\Http\UploadedFile($fixturePath, 'pv.pdf', 'application/pdf', null, true);

        $this->actingAs($syndicUser)->post(route('import.preview'), ['pdf' => $file]);
        $this->actingAs($syndicUser)->post(route('import.confirm'));

        // Verify fixed expenses store ALL data from PDF
        $electricite = Expense::where('label', 'Electricité parties communes')->first();
        $this->assertNotNull($electricite);
        $this->assertEquals('Electricité parties communes', $electricite->categorie);
        $this->assertEquals(100.50, $electricite->montant_mensuel);
        $this->assertEquals(1206.00, $electricite->amount);
        $this->assertEquals('EDF contrat 2026', $electricite->justificatif_pdf);
        $this->assertEquals('fixe', $electricite->type);
        $this->assertEquals('imported', $electricite->status);

        $eau = Expense::where('label', 'Eau parties communes')->first();
        $this->assertNotNull($eau);
        $this->assertEquals('Eau parties communes', $eau->categorie);
        $this->assertEquals(50.00, $eau->montant_mensuel);
        $this->assertEquals(600.00, $eau->amount);
        $this->assertEquals('Veolia', $eau->justificatif_pdf);

        // Verify variable expenses store ALL data from PDF
        $ascenseur = Expense::where('label', 'Réparation - Ascenseur')->first();
        $this->assertNotNull($ascenseur);
        $this->assertEquals('Ascenseur', $ascenseur->categorie);
        $this->assertEquals(5000.00, $ascenseur->amount);
        $this->assertEquals('Maintenance annuelle', $ascenseur->justificatif_pdf);
        $this->assertNull($ascenseur->montant_mensuel); // variable expenses don't have monthly
        $this->assertEquals('variable', $ascenseur->type);
        $this->assertEquals('imported', $ascenseur->status);

        $ravalement = Expense::where('label', 'Travaux - Ravalement')->first();
        $this->assertNotNull($ravalement);
        $this->assertEquals('Ravalement', $ravalement->categorie);
        $this->assertEquals(15000.00, $ravalement->amount);
        $this->assertEquals('Devis Entreprise X', $ravalement->justificatif_pdf);
        $this->assertNull($ravalement->montant_mensuel);
    }
}

