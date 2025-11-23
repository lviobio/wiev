<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Http\Controllers;

use App\Core\Auth\Login\Actions\LoginUserAction;
use App\Core\Auth\Login\Data\LoginData;
use App\Core\Auth\Login\Exceptions\InvalidCredentialsException;
use App\Core\Auth\Login\Http\Resources\LoginResultResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(LoginData $data, LoginUserAction $action): LoginResultResource
    {
        try {
            $result = $action($data);
        } catch (InvalidCredentialsException) {
            $firstFieldKey = Arr::first(array_keys($data::getValidationRules([])));

            throw ValidationException::withMessages([
                $firstFieldKey => [__('The provided credentials do not match our records.')],
            ]);
        }

        return LoginResultResource::make([
            'user' => $result->user,
            'issued' => $result->issuedCredentials,
        ]);
    }
}
