<?php

use App\Models\User;

beforeEach(fn () => $this->markTestSkipped('Feature disabled for SSO configuration'));

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();
});
