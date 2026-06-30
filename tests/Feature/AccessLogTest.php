<?php

use App\Models\User;
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
