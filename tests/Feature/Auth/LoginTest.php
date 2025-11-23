<?php
declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Validated;

test('login', function () {
    Event::fake();

    $password = Str::password();
    $user = User::factory()->withPassword($password)->create();

    $this->post('api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(
        status: 422
    )->assertJsonPath(
        path: 'message',
        expect: __('The provided credentials do not match our records.'),
    );

    Event::assertDispatchedOnce(Attempting::class);
    Event::assertDispatchedOnce(Failed::class);
    Event::assertNotDispatched(Validated::class);

    $response = $this->post('api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    Event::assertDispatchedTimes(Attempting::class, 2);
    Event::assertDispatchedOnce(Failed::class);
    Event::assertDispatchedOnce(Validated::class);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'user',
            'issued',
        ],
    ]);

    $response->assertJsonPath('data.user.id', $user->id);
    $response->assertJsonPath('data.user.email', $user->email);
    $response->assertJsonPath('data.issued.type', 'bearer');
});
