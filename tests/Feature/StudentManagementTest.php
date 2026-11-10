<?php

use App\Enums\UserRole;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->supervisor = User::factory()->supervisor()->create();
});

test('a supervisor can create a student', function () {
    $response = $this->actingAs($this->supervisor)->post(route('supervisor.students.store'), [
        'name' => 'Amina Rasoa',
        'email' => 'amina@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'phone' => '0340000000',
        'notes' => 'Étudiante sérieuse',
    ]);

    $student = User::where('email', 'amina@example.com')->first();

    expect($student)->not->toBeNull()
        ->and($student->role)->toBe(UserRole::Student)
        ->and($student->is_active)->toBeTrue()
        ->and(Hash::check('secret-password', $student->password))->toBeTrue();

    $response->assertRedirect(route('supervisor.students.show', $student));
});

test('student creation validates required fields and unique email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($this->supervisor)->post(route('supervisor.students.store'), [
        'name' => '',
        'email' => 'existing@example.com',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'email', 'password']);
});

test('a supervisor can update a student', function () {
    $student = User::factory()->create();

    $this->actingAs($this->supervisor)->patch(route('supervisor.students.update', $student), [
        'name' => 'Nouveau Nom',
        'email' => 'new-email@example.com',
        'phone' => '0330000000',
        'notes' => null,
    ])->assertRedirect(route('supervisor.students.show', $student));

    expect($student->refresh()->name)->toBe('Nouveau Nom')
        ->and($student->email)->toBe('new-email@example.com');
});

test('a supervisor can deactivate and reactivate a student', function () {
    $student = User::factory()->create();

    $this->actingAs($this->supervisor)->patch(route('supervisor.students.deactivate', $student));
    expect($student->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->supervisor)->patch(route('supervisor.students.activate', $student));
    expect($student->refresh()->is_active)->toBeTrue();
});

test('reset password returns a generated password and stores its hash', function () {
    $student = User::factory()->create();

    $response = $this->actingAs($this->supervisor)
        ->post(route('supervisor.students.reset-password', $student));

    $response->assertSessionHas('generated_password');

    $password = session('generated_password');
    expect(Hash::check($password, $student->refresh()->password))->toBeTrue();
});

test('deleting a student archives the account and preserves history', function () {
    $student = User::factory()->create();
    $submission = ExerciseSubmission::factory()->create(['user_id' => $student->id]);

    $this->actingAs($this->supervisor)
        ->delete(route('supervisor.students.destroy', $student))
        ->assertRedirect(route('supervisor.students.index'));

    $this->assertSoftDeleted('users', ['id' => $student->id]);
    $archived = User::withTrashed()->find($student->id);
    expect(User::find($student->id))->toBeNull()
        ->and($archived)->not->toBeNull()
        ->and($submission->fresh())->not->toBeNull();
});

test('a supervisor cannot view another supervisor as a student record', function () {
    $other = User::factory()->supervisor()->create();

    $this->actingAs($this->supervisor)
        ->get(route('supervisor.students.show', $other))
        ->assertNotFound();
});
