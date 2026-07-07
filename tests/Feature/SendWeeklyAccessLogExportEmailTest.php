<?php

use App\Jobs\SendWeeklyAccessLogExportEmail;
use App\Mail\WeeklyAccessLogExportMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('it sends email when job is processed', function () {
    Mail::fake();

    $user = User::factory()->create();
    $downloadUrl = 'https://example.com/download/test.csv';

    $job = new SendWeeklyAccessLogExportEmail($user, $downloadUrl);
    $job->handle();

    Mail::assertSent(WeeklyAccessLogExportMail::class, function ($mail) use ($user, $downloadUrl) {
        return $mail->hasTo($user->email) && $mail->downloadUrl === $downloadUrl;
    });
});
