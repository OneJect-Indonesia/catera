<?php

use App\Models\MdGroup;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to portal login', function () {
    $response = $this->get(route('groups.index'));
    $response->assertRedirect(config('services.sso.portal_url'));
});

test('unauthorized users without permission receive 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('groups.index'));
    $response->assertForbidden();
});

test('authorized users with view permission can view groups page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:md_group:view_any');
    $this->actingAs($user);

    $response = $this->get(route('groups.index'));
    $response->assertOk();
});

test('can view lists and paginate groups', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('catera:md_group:view_any');
    $this->actingAs($user);

    MdGroup::create(['nama_group' => 'group_xyz', 'short_description' => 'XYZ']);

    Livewire::test('pages::group.index')
        ->assertOk()
        ->assertViewHas('groups', function ($groups) {
            return $groups->count() >= 1;
        });
});

test('can create group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:md_group:view_any', 'catera:md_group:create']);
    $this->actingAs($user);

    Livewire::test('pages::group.index')
        ->call('openAddModal')
        ->set('addNamaGroup', 'hijau')
        ->set('addShortDescription', 'Group Hijau')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showAddModal', false);

    $this->assertDatabaseHas('md_groups', [
        'nama_group' => 'hijau',
        'short_description' => 'Group Hijau',
    ]);
});

test('can update group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:md_group:view_any', 'catera:md_group:update']);
    $this->actingAs($user);

    $group = MdGroup::create(['nama_group' => 'kuning', 'short_description' => 'Kuning']);

    Livewire::test('pages::group.index')
        ->call('edit', $group->id)
        ->set('editNamaGroup', 'kuning_updated')
        ->set('editShortDescription', 'Kuning Updated')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $group->refresh();
    expect($group->nama_group)->toBe('kuning_updated');
    expect($group->short_description)->toBe('Kuning Updated');
});

test('can delete group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['catera:md_group:view_any', 'catera:md_group:delete']);
    $this->actingAs($user);

    $group = MdGroup::create(['nama_group' => 'ungu', 'short_description' => 'Ungu']);

    Livewire::test('pages::group.index')
        ->call('confirmDelete', $group->id)
        ->call('destroy')
        ->assertSet('showDeleteModal', false);

    $this->assertDatabaseMissing('md_groups', [
        'id' => $group->id,
    ]);
});
