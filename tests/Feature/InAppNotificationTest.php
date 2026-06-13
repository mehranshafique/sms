<?php

use App\Models\User;
use App\Models\InAppNotification;
use Database\Seeders\PlatformSuperAdminSeeder;

test('notification mark read and mark all endpoints work', function () {
    $user = User::where('email', PlatformSuperAdminSeeder::EMAIL)->first();
    expect($user)->not->toBeNull();

    $before = InAppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    expect($before)->toBeGreaterThan(0);

    $this->actingAs($user);

    $feed = $this->getJson('/notifications/feed');
    $feed->assertOk()->assertJsonPath('ok', true);

    $notif = InAppNotification::where('user_id', $user->id)->whereNull('read_at')->first();
    $mark = $this->postJson("/notifications/{$notif->id}/read");
    $mark->assertOk()->assertJsonPath('ok', true);

    $mid = InAppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    expect($mid)->toBe($before - 1);

    $all = $this->postJson('/notifications/read-all');
    $all->assertOk()->assertJsonPath('ok', true)->assertJsonPath('unread_count', 0);

    $after = InAppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    expect($after)->toBe(0);
});
