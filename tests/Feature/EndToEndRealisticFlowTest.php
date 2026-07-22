<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\AccountActivationToken;
use App\Models\Budget;
use App\Models\Complaint;
use App\Models\Contribution;
use App\Models\Expense;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Owner;
use App\Models\OwnerInvitation;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EndToEndRealisticFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_realistic_syndic_and_owner_flow_from_registration_to_contributions(): void
    {
        Mail::fake();

        // Étape 1 : inscription syndic
        $syndicEmail = 'syndic.' . uniqid() . '@example.com';
        $syndicPassword = 'Password123!';
        $registerResponse = $this->post('/register', [
            'name' => 'Syndic Test',
            'email' => $syndicEmail,
            'password' => $syndicPassword,
            'password_confirmation' => $syndicPassword,
            'property_name' => 'Immeuble Test',
            'property_address' => '10 rue de Test',
        ]);
        $registerResponse->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => $syndicEmail,
            'role' => 'syndic',
        ]);
        $user = User::where('email', $syndicEmail)->first();
        $this->assertNull($user->email_verified_at);
        $syndicActivationToken = null;
        Mail::assertSent(AccountActivationMail::class, function ($mail) use ($syndicEmail, &$syndicActivationToken) {
            if ($mail->hasTo($syndicEmail)) {
                $syndicActivationToken = $mail->token;
                return true;
            }
            return false;
        });
        $this->assertNotNull($syndicActivationToken);

        // Étape 2 : activation syndic
        $activationForm = $this->get('/activate/' . $syndicActivationToken);
        $activationForm->assertStatus(200);
        $activateResponse = $this->post('/activate/' . $syndicActivationToken, [
            'password' => $syndicPassword,
            'password_confirmation' => $syndicPassword,
        ]);
        $activateResponse->assertRedirect(route('login'));
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Étape 3 : connexion syndic
        $loginResponse = $this->post('/login', [
            'email' => $syndicEmail,
            'password' => $syndicPassword,
        ]);
        $loginResponse->assertRedirect(route('dashboard'));

        // Étape 4 : création de la property et du syndic
        $syndic = $user->syndic;
        $this->assertNotNull($syndic);
        $property = $syndic->property;
        $this->assertNotNull($property);
        $this->assertEquals('Immeuble Test', $property->name);

        // Étape 5 : upload et validation du règlement de copropriété
        $reglementFile = UploadedFile::fake()->create('reglement.pdf', 100, 'application/pdf');
        $uploadResponse = $this->actingAs($user)->post('/properties/' . $property->id . '/reglement', [
            'reglement' => $reglementFile,
        ]);
        $uploadResponse->assertRedirect();
        $property->refresh();
        $this->assertNotNull($property->reglement_fichier);
        $publicResponse = $this->get('/');
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee(route('properties.reglement.download', $property));

        // Étape 6 : invitation et création de 3 copropriétaires
        $ownerEmails = [
            'owner1.' . uniqid() . '@example.com',
            'owner2.' . uniqid() . '@example.com',
            'owner3.' . uniqid() . '@example.com',
        ];
        $surfaces = [50, 70, 100];
        $ownerInvitations = [];
        foreach ($ownerEmails as $index => $ownerEmail) {
            $inviteResponse = $this->actingAs($user)->postJson('/invitations', [
                'email' => $ownerEmail,
                'property_id' => $property->id,
            ]);
            $inviteResponse->assertStatus(201);
            $ownerInvitations[$ownerEmail] = OwnerInvitation::where('email', $ownerEmail)->first();
            $this->assertNotNull($ownerInvitations[$ownerEmail]);
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('owners', 0);
        $this->assertEquals(3, OwnerInvitation::where('property_id', $property->id)->count());

        // Étape 7 : activation des 3 comptes copropriétaires
        $ownerUsers = [];
        foreach ($ownerEmails as $index => $ownerEmail) {
            $invitation = OwnerInvitation::where('email', $ownerEmail)->first();
            $this->assertNotNull($invitation);
            $token = null;
            Mail::assertSent(AccountActivationMail::class, function ($mail) use ($ownerEmail, &$token) {
                if ($mail->hasTo($ownerEmail)) {
                    $token = $mail->token;
                    return true;
                }
                return false;
            });
            $this->assertNotNull($token);
            $activatePage = $this->get('/activate/' . $token);
            $activatePage->assertStatus(200);
            $activateResponse = $this->post('/activate/' . $token, [
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'name' => 'Owner ' . ($index + 1),
                'email' => $ownerEmail,
                'status' => 'proprietaire',
                'is_tenant' => false,
                'lot_surface' => $surfaces[$index],
                'surface_confirmation' => $surfaces[$index],
                'has_mezzanine' => false,
            ]);
            $activateResponse->assertRedirect(route('login'));
            $ownerUser = User::where('email', $ownerEmail)->first();
            $this->assertNotNull($ownerUser);
            $this->assertNotNull($ownerUser->email_verified_at);
            $ownerUsers[$ownerEmail] = $ownerUser;
        }
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('owners', 3);

        // Étape 8 : connexion de chaque copropriétaire
        foreach ($ownerUsers as $ownerEmail => $ownerUser) {
            $ownerLoginResponse = $this->post('/login', [
                'email' => $ownerEmail,
                'password' => 'Password123!',
            ]);
            $ownerLoginResponse->assertRedirect(route('dashboard'));
        }

        // Étape 9 : création d'un budget
        $budgetResponse = $this->actingAs($user)->post('/budgets', [
            'year' => 2026,
            'fixed_charges_total' => 12000,
            'variable_charges_total' => 6000,
        ]);
        $budgetResponse->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'property_id' => $property->id,
            'year' => 2026,
            'is_valid' => false,
        ]);
        $budget = Budget::where('property_id', $property->id)->latest('id')->first();
        $this->assertNotNull($budget);

        // Étape 10 : validation du budget
        $validateResponse = $this->actingAs($user)->post('/budgets/' . $budget->id . '/validate');
        $validateResponse->assertRedirect(route('budgets.index'));
        $budget->refresh();
        $this->assertTrue((bool) $budget->is_valid);

        // Étape 11 : calcul des cotisations
        $calculateResponse = $this->actingAs($user)->post('/contributions/calculate', [
            'property_id' => $property->id,
        ]);
        $calculateResponse->assertRedirect(route('contributions.index'));
        $calculateResponse->assertSessionHas('success', 'Les cotisations ont été calculées avec succès.');
        $this->assertDatabaseCount('contributions', 3);
        $contributions = Contribution::where('budget_id', $budget->id)->orderBy('owner_id')->get();
        $this->assertCount(3, $contributions);
        $tantiemes = $contributions->pluck('tantieme')->all();
        $this->assertTrue(abs(array_sum($tantiemes) - 1000) <= 1);
        $amounts = $contributions->pluck('montant_annuel')->all();
        $this->assertGreaterThan($amounts[0], $amounts[2]);
        $this->assertGreaterThan($amounts[0], $amounts[1]);

        // Étape 12 : vérification du tableau de cotisations affiché
        $tableResponse = $this->actingAs($user)->get('/contributions');
        $tableResponse->assertStatus(200);
        foreach ($ownerUsers as $ownerEmail => $ownerUser) {
            $tableResponse->assertSee($ownerUser->name);
        }
        $tableResponse->assertSee('Montant annuel');
        $tableResponse->assertSee('Montant mensuel');

        // Étape 13 : vérification du déblocage des fonctionnalités copropriétaire
        foreach ($ownerUsers as $ownerEmail => $ownerUser) {
            $this->actingAs($ownerUser)->get('/complaints')->assertStatus(200);
            $this->actingAs($ownerUser)->get('/meetings')->assertStatus(200);
            $this->actingAs($ownerUser)->get('/messages')->assertStatus(200);
        }

        // Étape 14 : un copropriétaire crée une réclamation
        $firstOwner = $ownerUsers[array_key_first($ownerUsers)];
        $ownerRecord = Owner::where('user_id', $firstOwner->id)->first();
        $complaintResponse = $this->actingAs($firstOwner)->post('/complaints', [
            'owner_id' => $ownerRecord->id,
            'property_id' => $property->id,
            'subject' => 'Fuite d’eau',
            'description' => 'La fuite est visible dans l’entrée.',
        ]);
        $complaintResponse->assertRedirect(route('complaints.index'));
        $this->assertDatabaseHas('complaints', [
            'owner_id' => $ownerRecord->id,
            'property_id' => $property->id,
            'subject' => 'Fuite d’eau',
        ]);
        $syndicComplaintView = $this->actingAs($user)->get('/complaints');
        $syndicComplaintView->assertStatus(200);
        $syndicComplaintView->assertSee('Fuite d’eau');

        // Étape 15 : le syndic crée une réunion
        $meetingResponse = $this->actingAs($user)->post('/meetings', [
            'title' => 'Assemblée générale',
            'meeting_date' => now()->addWeek()->toDateString(),
            'agenda' => 'Ordre du jour principal',
            'property_id' => $property->id,
        ]);
        $meetingResponse->assertRedirect(route('meetings.index'));
        $this->assertDatabaseCount('notifications', 3);
        $this->assertTrue(Notification::where('title', 'like', '%Assemblée générale%')->exists());

        // Étape 16 : messagerie
        $messageResponse = $this->actingAs($firstOwner)->post('/messages', [
            'owner_id' => $ownerRecord->id,
            'subject' => 'Bonjour',
            'body' => 'Je vous envoie un message de test.',
        ]);
        $messageResponse->assertRedirect('/messages');
        $this->assertDatabaseHas('messages', [
            'owner_id' => $ownerRecord->id,
            'subject' => 'Bonjour',
            'body' => 'Je vous envoie un message de test.',
        ]);
        $syndicMessagesResponse = $this->actingAs($user)->get('/messages');
        $syndicMessagesResponse->assertStatus(200);
        $syndicMessagesResponse->assertSee('Contacts et conversations');
        $syndicMessagesResponse->assertSee('/messages/');

        // Étape 17 : création d'une dépense
        $expenseResponse = $this->actingAs($user)->post('/expenses', [
            'property_id' => $property->id,
            'label' => 'Entretien ascenseur',
            'amount' => 350.5,
            'expense_date' => now()->toDateString(),
            'type' => 'maintenance',
            'category' => 'maintenance',
        ]);
        $expenseResponse->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'property_id' => $property->id,
            'label' => 'Entretien ascenseur',
            'amount' => 350.5,
            'status' => 'pending',
        ]);
        $expenseListResponse = $this->actingAs($user)->get('/expenses');
        $expenseListResponse->assertStatus(200);
        $expenseListResponse->assertSee('Entretien ascenseur');
        $expenseListResponse->assertSee('350,50 €');
        $expenseListResponse->assertSee('Justificatif manquant');

        // Étape 18 : modification du profil personnel (anti-IDOR)
        $profileResponse = $this->actingAs($firstOwner)->patch('/profile', [
            'name' => 'Nouveau Nom Owner',
            'lot_surface' => 50,
            'surface_confirmation' => 50,
            'has_mezzanine' => false,
            'mezzanine_surface' => null,
            'office_number' => null,
            'floor' => null,
        ]);
        $profileResponse->assertRedirect(route('profile.show'));
        $firstOwner->refresh();
        $this->assertEquals('Nouveau Nom Owner', $firstOwner->name);
        $otherOwners = collect($ownerUsers)->reject(fn ($ownerUser) => $ownerUser->id === $firstOwner->id);
        foreach ($otherOwners as $otherOwner) {
            $otherOwner->refresh();
            $this->assertNotEquals('Nouveau Nom Owner', $otherOwner->name);
        }

        // Étape 19 : message d'erreur de connexion
        $wrongLoginResponse = $this->from('/login')->post('/login', [
            'email' => $firstOwner->email,
            'password' => 'WrongPassword123!',
        ]);
        $wrongLoginResponse->assertRedirect(route('login'));
        $wrongLoginResponse->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('incorrects', $wrongLoginResponse->getSession()->get('errors')->first('email'));
    }
}
