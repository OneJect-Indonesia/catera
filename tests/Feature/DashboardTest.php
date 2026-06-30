<?php

use App\Models\AccessLog;
use App\Models\Authorized;
use App\Models\QuotaSchedule;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(config('services.sso.portal_url'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users can view dashboard stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $auth1 = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    $auth2 = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'biru',
        'quota' => 5,
        'is_active' => false,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $auth1->uuid,
        'add_quota' => 50,
        'target_date' => now()->toDateString(),
        'status' => 'success',
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $auth1->uuid,
        'add_quota' => 100,
        'target_date' => now()->subMonth()->toDateString(),
        'status' => 'success',
    ]);

    AccessLog::create([
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'status' => 'authorized',
        'scanned_at' => now(),
    ]);

    AccessLog::create([
        'uuid' => (string) Str::uuid(),
        'group' => 'biru',
        'status' => 'inactive',
        'scanned_at' => now()->subDay(),
    ]);

    Livewire::test('pages::dashboard.index')
        ->assertOk()
        ->assertViewHas('stats', function ($stats) {
            return $stats['total_authorized'] === 2
                && $stats['merah_count'] === 1
                && $stats['biru_count'] === 1
                && $stats['active_count'] === 1
                && $stats['inactive_count'] === 1
                && $stats['total_quota'] === 50
                && $stats['total_access_logs'] === 1
                && $stats['access_success_count'] === 1
                && $stats['access_failed_count'] === 0;
        });
});
