<?php

use Illuminate\Support\Facades\Route;

test('email verification is disabled', function () {
    $this->get('/verify-email')->assertNotFound();
    expect(Route::has('verification.verify'))->toBeFalse();
});
