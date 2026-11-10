<?php

use App\Models\User;

test('password confirmation is disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/confirm-password')->assertNotFound();
    $this->actingAs($user)->post('/confirm-password', ['password' => 'password'])->assertNotFound();
});
