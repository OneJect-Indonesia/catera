<?php

use App\Models\AccessLog;
use App\Models\Authorized;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('local');
    Mail::fake();
    Permission::findOrCreate('catera:access_logs:view_any', 'web');
});

test('it exports weekly access logs and sends email to authorized users', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('catera:access_logs:view_any');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'nik' => '12345678',
    ]);

    $authorized = Authorized::create([
        'user_id' => $user->id,
        'uuid' => 'test-uuid-1',
        'group' => 'merah',
        'quota' => 5,
        'is_active' => true,
    ]);

    AccessLog::create([
        'authorizeds_id' => $authorized->id,
        'uuid' => $authorized->uuid,
        'group' => $authorized->group,
        'status' => 'authorized',
        'scanned_at' => now()->subDays(2),
    ]);

    $this->artisan('app:export-weekly-access-logs')->assertSuccessful();

    Storage::disk('local')->assertExists('exports/authorized_access_logs_'.now()->subWeek()->format('Y-m-d').'_to_'.now()->subDay()->format('Y-m-d').'.csv');

    $csvContent = Storage::disk('local')->get('exports/authorized_access_logs_'.now()->subWeek()->format('Y-m-d').'_to_'.now()->subDay()->format('Y-m-d').'.csv');
    expect($csvContent)->toContain('Full Name');
    expect($csvContent)->toContain('NIK');
    expect($csvContent)->toContain('Type');
    expect($csvContent)->toContain('Tanggal Pengambilan');
    expect($csvContent)->toContain('John Doe');
    expect($csvContent)->toContain('12345678');
    expect($csvContent)->toContain('merah');

    Mail::assertSent(\App\Mail\WeeklyAccessLogExportMail::class, function ($mail) use ($admin) {
        return $mail->hasTo($admin->email) && str_contains($mail->downloadUrl, '/exports/');
    });
});
