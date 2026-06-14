<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => $user->username,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.index'));

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'username' => $user->username,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.index'));

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('password can be updated from profile', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'new-password-1A',
            'password_confirmation' => 'new-password-1A',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.index'));
});
