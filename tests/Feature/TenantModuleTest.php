<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_syndic_can_create_tenant()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $user = \App\Models\User::create(['name' => 'SyndicTenant', 'email' => 'syndic_tenant@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);

        $response = $this->actingAs($user)->postJson('/tenants', ['owner_id' => $owner->id, 'contract_start_date' => now()->toDateString(), 'contract_end_date' => now()->addYear()->toDateString(), 'is_active' => true]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tenants', ['owner_id' => $owner->id]);
    }

    public function test_syndic_can_update_tenant()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $user = \App\Models\User::create(['name' => 'SyndicTenant2', 'email' => 'syndic_tenant2@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => now()->subYear()->toDateString(), 'contract_end_date' => now()->addYear()->toDateString(), 'is_active' => true]);

        $response = $this->actingAs($user)->putJson('/tenants/' . $tenant->id, ['contract_end_date' => now()->addMonths(6)->toDateString()]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'is_active' => true]);
    }

    public function test_syndic_can_delete_tenant()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $user = \App\Models\User::create(['name' => 'SyndicTenant3', 'email' => 'syndic_tenant3@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        \App\Models\Syndic::create(['user_id' => $user->id, 'property_id' => $property->id]);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => now()->toDateString(), 'contract_end_date' => now()->addYear()->toDateString(), 'is_active' => true]);

        $response = $this->actingAs($user)->deleteJson('/tenants/' . $tenant->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_owner_association_and_contract_dates()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => '2025-01-01', 'contract_end_date' => '2026-01-01', 'is_active' => true]);

        $this->assertEquals($tenant->owner->id, $owner->id);
        $this->assertEquals('2026-01-01', $tenant->contract_end_date->toDateString());
    }

    public function test_send_lease_ending_alerts_within_7_days()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => now()->subYear()->toDateString(), 'contract_end_date' => now()->addDays(5)->toDateString(), 'is_active' => true]);

        $this->artisan('tenants:send-lease-ending-alerts')->assertExitCode(0);

        $fresh = Tenant::find($tenant->id);
        $this->assertNotNull($fresh->reminder_sent_at);
    }

    public function test_lease_alert_not_sent_twice()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => now()->subYear()->toDateString(), 'contract_end_date' => now()->addDays(5)->toDateString(), 'is_active' => true, 'reminder_sent_at' => now()->subDay()]);

        $this->artisan('tenants:send-lease-ending-alerts')->assertExitCode(0);

        $fresh = Tenant::find($tenant->id);
        $this->assertNotNull($fresh->reminder_sent_at);
    }

    public function test_alert_not_sent_before_threshold()
    {
        $property = Property::create(['name' => 'Immeuble', 'address' => 'Rue', 'city' => 'Paris', 'postal_code' => '75000']);
        $owner = Owner::create(['property_id' => $property->id, 'lot_surface' => 10, 'surface_confirmation' => 10]);
        $tenant = Tenant::create(['owner_id' => $owner->id, 'contract_start_date' => now()->subYear()->toDateString(), 'contract_end_date' => now()->addDays(20)->toDateString(), 'is_active' => true]);

        $this->artisan('tenants:send-lease-ending-alerts')->assertExitCode(0);

        $fresh = Tenant::find($tenant->id);
        $this->assertNull($fresh->reminder_sent_at);
    }

    public function test_syndic_cannot_manage_tenant_of_other_property()
    {
        $propertyA = Property::create(['name' => 'A', 'address' => '1', 'city' => 'X', 'postal_code' => '00000']);
        $propertyB = Property::create(['name' => 'B', 'address' => '2', 'city' => 'Y', 'postal_code' => '11111']);

        $ownerB = Owner::create(['property_id' => $propertyB->id, 'lot_surface' => 12, 'surface_confirmation' => 12]);

        $userA = \App\Models\User::create(['name' => 'SyndicA', 'email' => 'syndicA@example.test', 'password' => bcrypt('secret'), 'role' => 'syndic']);
        \App\Models\Syndic::create(['user_id' => $userA->id, 'property_id' => $propertyA->id]);

        // Syndic of property A should not be able to create tenant for owner in property B
        $respCreate = $this->actingAs($userA)->postJson('/tenants', ['owner_id' => $ownerB->id, 'contract_start_date' => now()->toDateString(), 'contract_end_date' => now()->addYear()->toDateString(), 'is_active' => true]);
        $respCreate->assertStatus(403);

        // attempt update/delete on a tenant of property B
        $tenantB = Tenant::create(['owner_id' => $ownerB->id, 'contract_start_date' => now()->toDateString(), 'contract_end_date' => now()->addYear()->toDateString(), 'is_active' => true]);

        $respUpdate = $this->actingAs($userA)->putJson('/tenants/' . $tenantB->id, ['contract_end_date' => now()->addMonths(6)->toDateString()]);
        $respUpdate->assertStatus(403);

        $respDelete = $this->actingAs($userA)->deleteJson('/tenants/' . $tenantB->id);
        $respDelete->assertStatus(403);
    }
}
