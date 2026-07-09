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
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    $group = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);

    $testUuid = 'test-uuid-'.Str::random(8);
    Authorized::create([
        'user_id' => $user->id,
        'uuid' => $testUuid,
        'group_id' => $group->id,
        'quota' => 10,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('search', $testUuid)
        ->assertViewHas('authorizeds', function ($authorizeds) use ($testUuid) {
            return $authorizeds->count() === 1
                && $authorizeds->first()->uuid === $testUuid;
        });
});

test('search filters by group', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    $groupMerah = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);
    $groupBiru = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'biru']);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $groupMerah->id,
        'quota' => 10,
    ]);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $groupBiru->id,
        'quota' => 5,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('search', 'merah')
        ->assertViewHas('authorizeds', function ($authorizeds) {
            return $authorizeds->count() === 1
                && $authorizeds->first()->mdGroup->nama_group === 'merah';
        });
});

test('active filter works', function () {
    $user = User::factory()->create(['status' => 'active']);
    $userInactive = User::factory()->create(['status' => 'inactive']);
    $user->givePermissionTo('catera:authorized:view_any');
    $this->actingAs($user);

    $group = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);

    Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $group->id,
        'quota' => 10,
    ]);

    Authorized::create([
        'user_id' => $userInactive->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $group->id,
        'quota' => 5,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('activeOnly', true)
        ->assertViewHas('authorizeds', function ($authorizeds) {
            return $authorizeds->count() === 1
                && $authorizeds->first()->is_active === true;
        });
});

test('create authorized record with valid data', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    $group = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);

    $newUuid = (string) Str::uuid();

    Livewire::test('pages::authorized.index')
        ->call('openAddModal')
        ->assertSet('showAddModal', true)
        ->set('addUuid', $newUuid)
        ->set('addUserId', $user->id)
        ->set('addGroupId', $group->id)
        ->set('addQuota', '10')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showAddModal', false);

    $this->assertDatabaseHas('authorizeds', [
        'uuid' => $newUuid,
        'user_id' => $user->id,
        'group_id' => $group->id,
        'quota' => 10,
    ]);
});

test('create authorized fails with duplicate uuid', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    $group = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);

    $existing = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $group->id,
        'quota' => 10,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('addUuid', $existing->uuid)
        ->set('addUserId', $user->id)
        ->set('addGroupId', $group->id)
        ->set('addQuota', '10')
        ->call('store')
        ->assertHasErrors(['addUuid']);
});

test('create authorized fails with duplicate user_id', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:create']);
    $this->actingAs($user);

    $group = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);

    $existing = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $group->id,
        'quota' => 10,
    ]);

    Livewire::test('pages::authorized.index')
        ->set('addUuid', (string) Str::uuid())
        ->set('addUserId', $user->id)
        ->set('addGroupId', $group->id)
        ->set('addQuota', '10')
        ->call('store')
        ->assertHasErrors(['addUserId']);
});

test('update authorized record', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:update']);
    $this->actingAs($user);

    $groupMerah = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);
    $groupBiru = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'biru']);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $groupMerah->id,
        'quota' => 10,
    ]);

    Livewire::test('pages::authorized.index')
        ->call('edit', $authorized->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editUuid', $authorized->uuid)
        ->set('editGroupId', $groupBiru->id)
        ->set('editQuota', '20')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $authorized->refresh();
    expect($authorized->group_id)->toBe($groupBiru->id);
    expect($authorized->quota)->toBe(20);
});

test('delete authorized record', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo(['catera:authorized:view_any', 'catera:authorized:delete']);
    $this->actingAs($user);

    $group = \App\Models\MdGroup::firstOrCreate(['nama_group' => 'merah']);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group_id' => $group->id,
        'quota' => 10,
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
