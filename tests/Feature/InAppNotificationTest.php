<?php

use App\Models\User;
use App\Models\InAppNotification;
use Database\Seeders\PlatformSuperAdminSeeder;
use Database\Seeders\RolePermissionSeeder;

test('notification mark read and mark all endpoints work', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(PlatformSuperAdminSeeder::class);

    $user = User::where('email', PlatformSuperAdminSeeder::email())->first();
    expect($user)->not->toBeNull();

    InAppNotification::create([
        'user_id' => $user->id,
        'type' => 'test',
        'title' => 'Test notification',
        'message' => 'Unread test message',
    ]);
    InAppNotification::create([
        'user_id' => $user->id,
        'type' => 'test',
        'title' => 'Second notification',
        'message' => 'Another unread message',
    ]);

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
