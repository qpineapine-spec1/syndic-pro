<?php
require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\MeetingRequestController;
use App\Models\MeetingRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);

$user = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
$property = Property::create(['name' => 'Smoke Prop', 'address' => '1 Smoke']);
$owner = Owner::create([
    'property_id' => $property->id,
    'user_id' => $user->id,
    'lot_surface' => 50,
    'surface_confirmation' => 50,
]);

Auth::login($user);

$controller = new MeetingRequestController();
$storeResponse = $controller->store(new Request([
    'title' => 'Demande smoke',
    'motif' => 'Motif smoke',
    'property_id' => $property->id,
]));

$meetingRequest = MeetingRequest::latest()->first();
if (! $meetingRequest) {
    fwrite(STDERR, "No meeting request created\n");
    exit(1);
}

$secondUser = User::factory()->create(['role' => 'copropriétaire', 'email_verified_at' => now()]);
Owner::create([
    'property_id' => $property->id,
    'user_id' => $secondUser->id,
    'lot_surface' => 50,
    'surface_confirmation' => 50,
]);
Syndic::create(['user_id' => User::factory()->create(['role' => 'syndic', 'email_verified_at' => now()])->id, 'property_id' => $property->id]);

Auth::login($user);
$voteResponse = $controller->vote(new Request(), $meetingRequest);

$meetingRequest->refresh();

if ($storeResponse->getStatusCode() !== 302 || $meetingRequest->status !== 'triggered' || $meetingRequest->meeting_id === null) {
    fwrite(STDERR, "Smoke verification failed. Status={$meetingRequest->status}, meeting_id={$meetingRequest->meeting_id}\n");
    exit(1);
}

echo "Smoke OK: status={$meetingRequest->status}, meeting_id={$meetingRequest->meeting_id}\n";
