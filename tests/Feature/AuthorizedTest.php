<?php

use App\Models\Authorized;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('authorized.index'));
    $response->assertRedirect(config('services.sso.portal_url'));
});

test('authenticated users can visit the authorized page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    $response = $this->get(route('authorized.index'));
    $response->assertOk();
});

test('unauthorized users cannot visit the authorized page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('authorized.index'));
    $response->assertForbidden();
});

test('authorized list loads with pagination', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    Livewire::test('pages::authorized.index')
        ->assertOk()
        ->assertViewHas('authorizeds');
});

test('search filters by uuid', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    $testUuid = 'test-uuid-'.Str::random(8);
    Authorized::create([
        'user_id' => $user->id,
        'uuid' => $testUuid,
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('search', $testUuid)
        ->assertViewHas('authorizeds', function ($authorizeds) use ($testUuid) {
            return $authorizeds->count() === 1
                && $authorizeds->first()->uuid === $testUuid;
        });
});

test('search filters by group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'biru',
        'quota' => 5,
        'is_active' => true,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('search', 'merah')
        ->assertViewHas('authorizeds', function ($authorizeds) {
            return $authorizeds->count() === 1
                && $authorizeds->first()->group === 'merah';
        });
});

test('active filter works', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'biru',
        'quota' => 5,
        'is_active' => false,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('activeOnly', true)
        ->assertViewHas('authorizeds', function ($authorizeds) {
            return $authorizeds->count() === 1
                && $authorizeds->first()->is_active === true;
        });
});

test('create authorized record with valid data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    $newUuid = (string) Str::uuid();

    Livewire::test('pages::authorized.index')
        ->call('openAddModal')
        ->assertSet('showAddModal', true)
        ->set('addUuid', $newUuid)
        ->set('addUserId', $user->id)
        ->set('addGroup', 'merah')
        ->set('addQuota', '10')
        ->set('addIsActive', true)
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showAddModal', false);

    $this->assertDatabaseHas('authorizeds', [
        'uuid' => $newUuid,
        'user_id' => $user->id,
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);
});

test('create authorized fails with duplicate uuid', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    $existing = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('addUuid', $existing->uuid)
        ->set('addUserId', $user->id)
        ->set('addGroup', 'merah')
        ->set('addQuota', '10')
        ->set('addIsActive', true)
        ->call('store')
        ->assertHasErrors(['addUuid']);
});

test('create authorized fails with duplicate user_id', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    $existing = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('addUuid', (string) Str::uuid())
        ->set('addUserId', $user->id)
        ->set('addGroup', 'merah')
        ->set('addQuota', '10')
        ->set('addIsActive', true)
        ->call('store')
        ->assertHasErrors(['addUserId']);
});

test('update authorized record', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:update']);
    $this->actingAs($user);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::authorized.index')
        ->call('edit', $authorized->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editUuid', $authorized->uuid)
        ->set('editGroup', 'biru')
        ->set('editQuota', '20')
        ->set('editIsActive', false)
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $authorized->refresh();
    expect($authorized->group)->toBe('biru');
    expect($authorized->quota)->toBe(20);
    expect($authorized->is_active)->toBeFalse();
});

test('delete authorized record', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:delete']);
    $this->actingAs($user);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::authorized.index')
        ->call('confirmDelete', $authorized->id)
        ->assertSet('showDeleteModal', true)
        ->call('destroy')
        ->assertSet('showDeleteModal', false);

    $this->assertDatabaseMissing('authorizeds', [
        'id' => $authorized->id,
    ]);
});

test('portal users query only runs when add modal is open', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    Livewire::test('pages::authorized.index')
        ->assertViewHas('portalUsers', []);
});

test('portal users query returns results when add modal is open', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    Livewire::test('pages::authorized.index')
        ->call('openAddModal')
        ->assertSet('showAddModal', true)
        ->assertViewHas('portalUsers', function ($portalUsers) {
            return count($portalUsers) > 0;
        });
});
