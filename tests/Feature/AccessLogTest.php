<?php

use App\Models\AccessLog;
use App\Models\Authorized;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('catera:access_logs:view_any', 'web');
});

test('guests are redirected to portal login', function () {
    $response = $this->get(route('access_logs.index'));
    $response->assertRedirect(config('services.sso.portal_url'));
});

test('unauthorized users without permission receive 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('access_logs.index'));
    $response->assertStatus(403);
});

test('authorized users with view permission can view access logs page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:access_logs:view_any');
    $this->actingAs($user);

    $response = $this->get(route('access_logs.index'));
    $response->assertOk();
});

test('authorized users can export access logs with correct calculations', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:access_logs:view_any');
    $this->actingAs($user);

    $portalUser1 = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $portalUser2 = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

    $groupMerah = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);
    $groupBiru = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'biru']);

    $authMerah = Authorized::create([
        'user_id' => $portalUser1->id,
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'group_id' => $groupMerah->id,
        'quota' => 5,
    ]);

    $authBiru = Authorized::create([
        'user_id' => $portalUser2->id,
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'group_id' => $groupBiru->id,
        'quota' => 5,
    ]);

    AccessLog::create([
        'authorizeds_id' => $authMerah->id,
        'uuid' => $authMerah->uuid,
        'group' => 'merah',
        'status' => 'authorized',
        'scanned_at' => now()->subDays(2),
    ]);

    AccessLog::create([
        'authorizeds_id' => $authBiru->id,
        'uuid' => $authBiru->uuid,
        'group' => 'biru',
        'status' => 'authorized',
        'scanned_at' => now()->subDays(2),
    ]);

    AccessLog::create([
        'authorizeds_id' => $authBiru->id,
        'uuid' => $authBiru->uuid,
        'group' => 'biru',
        'status' => 'no quota',
        'scanned_at' => now()->subDays(2),
    ]);

    $response = Livewire::test('pages::access_logs.index')
        ->set('exportStartDate', now()->subDays(5)->toDateString())
        ->set('exportEndDate', now()->toDateString())
        ->call('export');

    $response->assertStatus(200);

    $filename = 'access_logs_export_'.now()->subDays(5)->format('Y-m-d').'_to_'.now()->format('Y-m-d').'.csv';
    $response->assertFileDownloaded($filename);
});
