<?php

use App\Models\Authorized;
use App\Models\QuotaSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('schedule add quota command processes pending schedules for today', function () {
    $user = User::factory()->create();
    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 5,
        'target_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Artisan::call('app:schedule-add-quota');

    $authorized->refresh();
    expect($authorized->quota)->toBe(6);

    $schedule = QuotaSchedule::first();
    expect($schedule->status)->toBe('success');
});

test('schedule add quota command processes pending schedules from past dates', function () {
    $user = User::factory()->create();
    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 3,
        'target_date' => now()->subDays(2)->toDateString(),
        'status' => 'pending',
    ]);

    Artisan::call('app:schedule-add-quota');

    $authorized->refresh();
    expect($authorized->quota)->toBe(4);

    $schedule = QuotaSchedule::first();
    expect($schedule->status)->toBe('success');
});

test('schedule add quota command does not process future schedules', function () {
    $user = User::factory()->create();
    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 5,
        'target_date' => now()->addDays(2)->toDateString(),
        'status' => 'pending',
    ]);

    Artisan::call('app:schedule-add-quota');

    $authorized->refresh();
    expect($authorized->quota)->toBe(1);

    $schedule = QuotaSchedule::first();
    expect($schedule->status)->toBe('pending');
});

test('schedule add quota command sets status to failed for inactive users', function () {
    $user = User::factory()->create();
    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => false,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 5,
        'target_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Artisan::call('app:schedule-add-quota');

    $authorized->refresh();
    expect($authorized->quota)->toBe(1);

    $schedule = QuotaSchedule::first();
    expect($schedule->status)->toBe('failed');
});

test('schedule add quota command does not process already successful schedules', function () {
    $user = User::factory()->create();
    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 5,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 10,
        'target_date' => now()->toDateString(),
        'status' => 'success',
    ]);

    Artisan::call('app:schedule-add-quota');

    $authorized->refresh();
    expect($authorized->quota)->toBe(5);
});

test('schedule add quota command processes multiple schedules', function () {
    $user1 = User::factory()->create();
    $authorized1 = Authorized::create([
        'user_id' => $user1->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    $user2 = User::factory()->create();
    $authorized2 = Authorized::create([
        'user_id' => $user2->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'biru',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized1->uuid,
        'add_quota' => 3,
        'target_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized2->uuid,
        'add_quota' => 7,
        'target_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Artisan::call('app:schedule-add-quota');

    $authorized1->refresh();
    $authorized2->refresh();

    expect($authorized1->quota)->toBe(4);
    expect($authorized2->quota)->toBe(8);

    $schedules = QuotaSchedule::all();
    expect($schedules->every(fn ($s) => $s->status === 'success'))->toBeTrue();
});

test('livewire prevents duplicate schedule for same user and date', function () {
    $this->seed(\Database\Seeders\PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo(['catera:quota_scheduling:view_any', 'catera:quota_scheduling:create']);
    $this->actingAs($user);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 5,
        'target_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Livewire::test('pages::quota_schedule.index')
        ->set('addAuthorizedUuid', $authorized->uuid)
        ->set('addAddQuota', 3)
        ->set('addTargetDate', now()->toDateString())
        ->call('store')
        ->assertDispatched('notify', message: 'A schedule already exists for this user on the selected date.', variant: 'danger');

    expect(QuotaSchedule::count())->toBe(1);
});

test('livewire allows schedule creation if previous schedule failed', function () {
    $this->seed(\Database\Seeders\PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo(['catera:quota_scheduling:view_any', 'catera:quota_scheduling:create']);
    $this->actingAs($user);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 5,
        'target_date' => now()->toDateString(),
        'status' => 'failed',
    ]);

    Livewire::test('pages::quota_schedule.index')
        ->set('addAuthorizedUuid', $authorized->uuid)
        ->set('addAddQuota', 3)
        ->set('addTargetDate', now()->toDateString())
        ->call('store')
        ->assertDispatched('notify', message: 'Scheduled quota setup successfully.', variant: 'success');

    expect(QuotaSchedule::count())->toBe(2);
});

test('livewire allows schedule creation for different dates', function () {
    $this->seed(\Database\Seeders\PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo(['catera:quota_scheduling:view_any', 'catera:quota_scheduling:create']);
    $this->actingAs($user);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => (string) Str::uuid(),
        'group' => 'merah',
        'quota' => 1,
        'is_active' => true,
    ]);

    QuotaSchedule::create([
        'authorized_uuid' => $authorized->uuid,
        'add_quota' => 5,
        'target_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Livewire::test('pages::quota_schedule.index')
        ->set('addAuthorizedUuid', $authorized->uuid)
        ->set('addAddQuota', 3)
        ->set('addTargetDate', now()->addDay()->toDateString())
        ->call('store')
        ->assertDispatched('notify', message: 'Scheduled quota setup successfully.', variant: 'success');

    expect(QuotaSchedule::count())->toBe(2);
});
